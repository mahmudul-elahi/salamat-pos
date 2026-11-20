<?php

namespace App\Http\Controllers\Hrm;

use App\Http\Controllers\Controller;
use App\Models\Customization\Customization;
use App\Models\Hrm\Attendance;
use App\Models\Hrm\Holiday;
use App\Models\Hrm\LeaveRequest;
use App\Models\Hrm\EmployeeLeave;
use App\Models\Hrm\WorkShift;
use App\Models\User;
use App\Services\Hrm\WorkShiftScheduleService;
use App\Services\Setting\SettingService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

/**
 * Backs the "Employee Details" page tabs that aren't already covered by
 * EmployeeController (the CRUD list/modal) — profile header summary,
 * personal/contact/address info, attendance stats, leave summary, and job
 * history.
 */
class EmployeeDetailController extends Controller
{
    public function __construct(protected WorkShiftScheduleService $scheduleService) {}

    /**
     * Header strip: name/photo/employee id/last check in-out/shift/today's
     * worked duration.
     */
    public function summary(User $employee)
    {
        $employee->loadMissing(['employeeProfile', 'workShifts', 'currentDepartment.department.workShifts']);

        $today = now()->toDateString();
        $todayAttendance = Attendance::query()
            ->where('user_id', $employee->id)
            ->where('in_date', $today)
            ->with('details')
            ->first();

        $latestAttendance = $todayAttendance ?? Attendance::query()
            ->where('user_id', $employee->id)
            ->latest('in_date')
            ->with('details')
            ->first();

        $details = $latestAttendance?->details->sortBy('in_time')->values() ?? collect();
        $firstIn = $details->first();
        $lastOut = $details->filter(fn($d) => $d->out_time)->sortBy('out_time')->last();

        $todaySeconds = $todayAttendance
            ? $todayAttendance->details->sum(fn($d) => $this->workedSeconds($d))
            : 0;

        return [
            'id' => $employee->id,
            'name' => $employee->name,
            'profile_picture' => $employee->profile_picture,
            'employee_code' => $employee->employeeProfile?->employee_id,
            'last_check_in' => $firstIn?->in_time?->format('h:i A'),
            'last_check_out' => $lastOut?->out_time?->format('h:i A'),
            'work_shift' => ($employee->workShifts->first() ?? $employee->currentDepartment?->department?->workShifts?->first())?->shift_name,
            'today_work_duration' => $this->formatSecondsAsTime($todaySeconds),
        ];
    }

    public function uploadAvatar(Request $request, User $employee)
    {
        $request->validate([
            'profile_picture' => ['required', 'image', 'mimes:jpeg,png,jpg,svg', 'max:2048'],
        ]);

        $employee->clearMediaCollection('profile_picture');
        $media = $employee->addMediaFromRequest('profile_picture')->toMediaCollection('profile_picture');

        return successResponse('Profile picture updated successfully', [
            'profile_picture' => $media->getFullUrl(),
        ]);
    }

    /**
     * Personal / contact / address info — one combined GET, the frontend's
     * three sub-forms each PATCH only the fields they own.
     */
    public function profile(User $employee)
    {
        $employee->loadMissing(['employeeProfile', 'currentDesignation.designation', 'currentDepartment.department', 'currentEmployeeStatus.employeeStatus', 'roles', 'tenants']);
        $profile = $employee->employeeProfile;

        return [
            'name' => $employee->name,
            'email' => $employee->email,
            'gender' => $profile?->gender,
            'date_of_birth' => $profile?->date_of_birth?->format('Y-m-d'),
            'place_of_birth' => $profile?->place_of_birth,
            'married_status' => $profile?->married_status,
            'religion' => $profile?->religion,
            'blood_type' => $profile?->blood_type,
            'nid_no' => $profile?->nid_no,
            'tin_no' => $profile?->tin_no,
            'phone_number' => $profile?->phone_number,
            'phone_country' => $profile?->phone_country,
            'job_title' => $employee->currentDesignation?->designation?->name,
            'joining_date' => $profile?->joining_date?->format('Y-m-d'),
            'designation' => $employee->currentDesignation?->designation?->name,
            'department' => $employee->currentDepartment?->department?->name,
            'employment_status' => $employee->currentEmployeeStatus?->employeeStatus?->name,
            'employment_status_color' => $employee->currentEmployeeStatus?->employeeStatus?->color,
            'role' => $employee->roles->first()?->name,
            'tenants' => $employee->tenants->pluck('name')->toArray(),
        ];
    }

    public function updateProfile(Request $request, User $employee)
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'unique:users,email,' . $employee->id],
            'gender' => ['sometimes', 'nullable', 'in:male,female,others'],
            'date_of_birth' => ['sometimes', 'nullable', 'date'],
            'place_of_birth' => ['sometimes', 'nullable', 'string', 'max:255'],
            'married_status' => ['sometimes', 'nullable', 'string', 'max:255'],
            'religion' => ['sometimes', 'nullable', 'string', 'max:255'],
            'blood_type' => ['sometimes', 'nullable', 'in:a+,a-,b+,b-,o+,o-,ab+,ab-'],
            'nid_no' => ['sometimes', 'nullable', 'string', 'max:255'],
            'tin_no' => ['sometimes', 'nullable', 'string', 'max:255'],
            'job_level' => ['sometimes', 'nullable', 'string', 'max:255'],
            'phone_number' => ['sometimes', 'nullable', 'string', 'max:30'],
            'phone_country' => ['sometimes', 'nullable', 'string', 'max:10'],
            'residential_address' => ['sometimes', 'nullable', 'string'],
            'permanent_address' => ['sometimes', 'nullable', 'string'],
        ]);

        $userFields = array_intersect_key($validated, ['name' => null, 'email' => null]);
        if (!empty($userFields)) {
            $employee->update($userFields);
        }

        $profileFields = array_diff_key($validated, ['name' => null, 'email' => null]);
        if (!empty($profileFields)) {
            $employee->employeeProfile()->updateOrCreate([], $profileFields);
        }

        return successResponse('Employee profile updated successfully');
    }

    /**
     * Pie-chart day counts + the hours summary card for the Attendance tab.
     */
    public function attendanceStats(Request $request, User $employee)
    {
        $month = (int) $request->input('month', now()->month);
        $year = (int) $request->input('year', now()->year);

        $daysInMonth = Carbon::createFromDate($year, $month, 1)->daysInMonth;
        $start = Carbon::createFromDate($year, $month, 1)->startOfDay();
        $end = Carbon::createFromDate($year, $month, $daysInMonth)->startOfDay();

        $employee->loadMissing(['workShifts', 'currentDepartment.department.workShifts']);
        $department = $employee->currentDepartment?->department;
        $shift = $employee->workShifts->first() ?? $department?->workShifts?->first();
        $weekendDays = $shift?->weekend_days ?? [];

        $holidayDates = $this->holidayDates($start, $end, $department?->id);
        $paidLeaveDates = $this->paidLeaveDates($employee->id, $start, $end);

        $attendanceByDate = Attendance::query()
            ->where('user_id', $employee->id)
            ->whereBetween('in_date', [$start->toDateString(), $end->toDateString()])
            ->whereNotIn('status', ['rejected', 'cancel'])
            ->with('details')
            ->get()
            ->keyBy(fn(Attendance $a) => $a->in_date->toDateString());

        // "leave" isn't one of the 6 pie-chart categories the UI asks for,
        // but it's tracked here too so the counts still add up to the full
        // number of days in the period instead of silently dropping leave
        // days out of the total.
        $counts = ['late' => 0, 'early' => 0, 'regular' => 0, 'absent' => 0, 'holiday' => 0, 'weekend' => 0, 'leave' => 0];
        $attendanceSeconds = 0;
        $scheduledSeconds = 0;

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $dateKey = $date->toDateString();

            if (in_array($date->format('l'), $weekendDays, true)) {
                $counts['weekend']++;
                continue;
            }

            if ($holidayDates->contains($dateKey)) {
                $counts['holiday']++;
                continue;
            }

            // Scheduled hours count for all working days, regardless of leave.
            // Weekends and holidays are already excluded above via continue.
            $scheduledSeconds += $this->scheduleService->scheduledSeconds($shift, $date);

            $attendance = $attendanceByDate->get($dateKey);

            // Always accumulate actual worked seconds even when the day is also
            // covered by an approved leave (employee may have come in anyway).
            if ($attendance) {
                $attendanceSeconds += $attendance->details->sum(fn($d) => $this->workedSeconds($d));
            }

            if ($paidLeaveDates->contains($dateKey)) {
                $counts['leave']++;
                continue;
            }

            if (!$attendance) {
                $counts['absent']++;
                continue;
            }

            $behavior = $attendance->behavior ?? 'regular';
            $counts[$behavior] = ($counts[$behavior] ?? 0) + 1;
        }

        $leaveSeconds = $this->paidLeaveSeconds($employee->id, $start, $end, $shift);
        $balanceSeconds = max(0, $scheduledSeconds - $attendanceSeconds - $leaveSeconds);
        $availability = $scheduledSeconds > 0 ? round($attendanceSeconds / $scheduledSeconds * 100, 2) : 0;

        $markedDays = $counts['late'] + $counts['regular'] + $counts['absent'] + $counts['early'];
        $percent = fn(string $key) => $markedDays > 0 ? round($counts[$key] / $markedDays * 100, 1) : 0;

        $threshold = (int) Customization::where('type', 'attendance')
            ->where('name', 'work_availability_percentage')
            ->value('value');

        return [
            'counts' => $counts,
            'average_behavior' => [
                'late'    => $percent('late'),
                'regular' => $percent('regular'),
                'absent'  => $percent('absent'),
                'early'   => $percent('early'),
            ],
            'work_shift_hours'             => $this->formatSecondsAsTime($scheduledSeconds),
            'attendance_hours'             => $this->formatSecondsAsTime($attendanceSeconds),
            'leave_hours'                  => $this->formatSecondsAsTime($leaveSeconds),
            'balance_hours'                => $this->formatSecondsAsTime($balanceSeconds),
            'work_availability_percent'    => $availability,
            'work_availability_threshold'  => $threshold,
            'work_availability_status'     => $threshold > 0
                ? ($availability >= $threshold ? 'good' : 'bad')
                : null,
        ];
    }

    /**
     * One status per calendar day (Attendance Calendar tab) — the same
     * priority rules as attendanceStats(), just returned per-day instead of
     * aggregated into counts.
     */
    public function attendanceCalendar(Request $request, User $employee)
    {
        $month = (int) $request->input('month', now()->month);
        $year = (int) $request->input('year', now()->year);

        $daysInMonth = Carbon::createFromDate($year, $month, 1)->daysInMonth;
        $start = Carbon::createFromDate($year, $month, 1)->startOfDay();
        $end = Carbon::createFromDate($year, $month, $daysInMonth)->startOfDay();

        $employee->loadMissing(['workShifts', 'currentDepartment.department.workShifts']);
        $department = $employee->currentDepartment?->department;
        $shift = $employee->workShifts->first() ?? $department?->workShifts?->first();
        $weekendDays = $shift?->weekend_days ?? [];

        $holidayDates = $this->holidayDates($start, $end, $department?->id);
        $paidLeaveDates = $this->paidLeaveDates($employee->id, $start, $end);

        $attendanceByDate = Attendance::query()
            ->where('user_id', $employee->id)
            ->whereBetween('in_date', [$start->toDateString(), $end->toDateString()])
            ->whereNotIn('status', ['rejected', 'cancel'])
            ->get()
            ->keyBy(fn(Attendance $a) => $a->in_date->toDateString());

        $days = [];

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $dateKey = $date->toDateString();

            if (in_array($date->format('l'), $weekendDays, true)) {
                $days[$dateKey] = 'weekend';
            } elseif ($holidayDates->contains($dateKey)) {
                $days[$dateKey] = 'holiday';
            } elseif ($paidLeaveDates->contains($dateKey)) {
                $days[$dateKey] = 'leave';
            } elseif ($attendance = $attendanceByDate->get($dateKey)) {
                $days[$dateKey] = $attendance->behavior ?? 'regular';
            } else {
                $days[$dateKey] = 'absent';
            }
        }

        return $days;
    }

    /**
     * Per-leave-type allowance summary (allowance, earned, taken, availability)
     * for the Leave Allowances tab.
     *
     * AssignEmployeeLeavesJob hands out one EmployeeLeave row per month, each
     * carrying that month's `day` entitlement — so `allowance` is the monthly
     * rate and `earned` is the sum of the months assigned so far.
     * `availability` is measured against what's been earned.
     */
    public function leaveAllowances(User $employee, SettingService $settingService): array
    {
        [$yearStart, $yearEnd] = $this->currentLeaveYear($settingService);

        $approvedLeaves = LeaveRequest::query()
            ->where('user_id', $employee->id)
            ->where('status', 'approved')
            ->get()
            ->groupBy('leave_type_id');

        // Only show leave types explicitly assigned to this employee, and only
        // the months belonging to the leave year currently running.
        $employeeLeaves = EmployeeLeave::query()
            ->where('employee_id', $employee->id)
            ->whereBetween('start_date', [$yearStart->toDateString(), $yearEnd->toDateString()])
            ->with('leaveType')
            ->orderBy('start_date')
            ->get()
            ->groupBy('leave_type_id');

        return $employeeLeaves
            ->map(function (Collection $months, $leaveTypeId) use ($approvedLeaves, $yearStart, $yearEnd) {
                $type     = $months->first()->leaveType;
                $requests = $approvedLeaves->get($leaveTypeId, collect());

                // Only leaves falling inside this leave year count against it.
                $taken = $requests
                    ->filter(fn(LeaveRequest $leave) => $this->leaveFallsInYear($leave, $yearStart, $yearEnd))
                    ->sum(function (LeaveRequest $leave) {
                        return match ($leave->leave_duration) {
                            'multi_day' => ($leave->start_date && $leave->end_date)
                                ? $leave->start_date->diffInDays($leave->end_date) + 1
                                : 0,
                            'single_day' => 1,
                            default      => 0,
                        };
                    });

                // The latest assigned month carries the rate in force today.
                $allowance = (float) ($months->last()->day ?? 0);

                $earned = (float) $months->sum('day');

                $availability = max(0, $earned - $taken);

                return [
                    'id'              => $type?->id,
                    'name'            => $type?->name,
                    'type'            => $type?->type,
                    'allowance'       => round($allowance, 2),
                    'months_assigned' => $months->count(),
                    'earned'          => round($earned, 2),
                    'taken'           => round((float) $taken, 2),
                    'availability'    => round($availability, 2),
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * The leave year currently running, derived from the `start_month` leave
     * setting exactly as AssignEmployeeLeavesJob derives it, so the tab and the
     * assignment job always agree on which months belong together.
     *
     * @return array{0: Carbon, 1: Carbon} start, end (inclusive)
     */
    private function currentLeaveYear(SettingService $settingService): array
    {
        $startMonth = (int) ($settingService->getFormattedSettings('leave')['start_month'] ?? 1);

        $today     = Carbon::now();
        $startYear = $today->month >= $startMonth ? $today->year : $today->year - 1;

        $start = Carbon::createFromDate($startYear, $startMonth, 1)->startOfMonth();
        $end   = $start->copy()->addYear()->subDay();

        return [$start, $end];
    }

    /**
     * Whether an approved leave lands inside the given leave year — `date` for
     * single-day/hourly requests, `start_date` for multi-day ones.
     */
    private function leaveFallsInYear(LeaveRequest $leave, Carbon $yearStart, Carbon $yearEnd): bool
    {
        $taken = $leave->start_date ?? $leave->date;

        if (! $taken) {
            return false;
        }

        return $taken->betweenIncluded($yearStart, $yearEnd);
    }

    /**
     * Pending / approved / rejected counts + the list, for the Leave
     * Management tab.
     */
    public function leaveSummary(Request $request, User $employee)
    {
        $month = (int) $request->input('month', now()->month);
        $year  = (int) $request->input('year',  now()->year);

        $start = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $end   = Carbon::createFromDate($year, $month, 1)->endOfMonth();

        $leaveRequests = LeaveRequest::query()
            ->where('user_id', $employee->id)
            ->with('leaveType:id,name,type')
            ->where(
                fn($q) => $q
                    ->whereBetween('start_date', [$start, $end])
                    ->orWhereBetween('date', [$start, $end])
            )
            ->latest('id')
            ->get();

        return [
            'counts' => [
                'pending'  => $leaveRequests->where('status', 'pending')->count(),
                'approved' => $leaveRequests->where('status', 'approved')->count(),
                'rejected' => $leaveRequests->where('status', 'rejected')->count(),
            ],
            'requests' => $leaveRequests->map(fn(LeaveRequest $leave) => [
                'id'             => $leave->id,
                'leave_type'     => $leave->leaveType?->name,
                'leave_duration' => $leave->leave_duration,
                'date'           => $leave->date?->format('d M Y'),
                'start_date'     => $leave->start_date?->format('d M Y'),
                'end_date'       => $leave->end_date?->format('d M Y'),
                'note'           => $leave->note,
                'attachments'    => $leave->attachments ?? [],
                'status'         => $leave->status,
            ])->values(),
        ];
    }

    /**
     * Current + historical department/designation/status/salary records for
     * the Job History tab.
     */
    public function jobHistory(User $employee)
    {
        $employee->loadMissing([
            'employeeDepartments.department',
            'employeeDesignations.designation',
            'employeeStatuses.employeeStatus',
            'employeeSalaries',
        ]);

        $mapRange = fn($item, $labelKey, $label) => [
            'id' => $item->id,
            'label' => $item->{$labelKey}?->{$label},
            'start_date' => $item->start_date?->format('Y-m-d'),
            'end_date' => $item->end_date?->format('Y-m-d'),
            'is_current' => $item->end_date === null,
        ];

        return [
            'departments' => $employee->employeeDepartments->sortByDesc('start_date')
                ->map(fn($d) => $mapRange($d, 'department', 'name'))->values(),
            'designations' => $employee->employeeDesignations->sortByDesc('start_date')
                ->map(fn($d) => $mapRange($d, 'designation', 'name'))->values(),
            'statuses' => $employee->employeeStatuses->sortByDesc('start_date')
                ->map(fn($s) => $mapRange($s, 'employeeStatus', 'name'))->values(),
            'salaries' => $employee->employeeSalaries->sortByDesc('start_date')->map(fn($s) => [
                'id' => $s->id,
                'amount' => $s->amount,
                'start_date' => $s->start_date?->format('Y-m-d'),
                'end_date' => $s->end_date?->format('Y-m-d'),
                'is_current' => $s->end_date === null,
            ])->values(),
        ];
    }

    public function updateJobHistory(Request $request, User $employee)
    {
        $data = $request->validate([
            'type'               => ['required', Rule::in(['department', 'designation', 'status', 'salary'])],
            'department_id'      => ['required_if:type,department', 'nullable', 'exists:departments,id'],
            'designation_id'     => ['required_if:type,designation', 'nullable', 'exists:designations,id'],
            'employee_status_id' => ['required_if:type,status', 'nullable', 'exists:employee_statuses,id'],
            'amount'             => ['required_if:type,salary', 'nullable', 'numeric', 'min:0'],
        ]);

        $effectiveDate = now()->toDateString();

        match ($data['type']) {
            'department'  => $this->transitionHistory(
                $employee->currentDepartment,
                $employee->employeeDepartments(),
                'department_id',
                $data['department_id'],
                ['department_id' => $data['department_id']],
                $effectiveDate,
                'Department is already set to the selected value.'
            ),
            'designation' => $this->transitionHistory(
                $employee->currentDesignation,
                $employee->employeeDesignations(),
                'designation_id',
                $data['designation_id'],
                ['designation_id' => $data['designation_id']],
                $effectiveDate,
                'Designation is already set to the selected value.'
            ),
            'status'      => $this->transitionHistory(
                $employee->currentEmployeeStatus,
                $employee->employeeStatuses(),
                'employee_status_id',
                $data['employee_status_id'],
                ['employee_status_id' => $data['employee_status_id']],
                $effectiveDate,
                'Employee status is already set to the selected value.'
            ),
            'salary'      => $this->transitionHistory(
                $employee->currentSalary,
                $employee->employeeSalaries(),
                'amount',
                $data['amount'],
                ['amount' => $data['amount']],
                $effectiveDate,
                'Salary is already set to this amount.',
                fn($a, $b) => abs((float) $a - (float) $b) < 0.01
            ),
        };

        return successResponse('Job history updated successfully.');
    }

    private function transitionHistory(
        $current,
        $relation,
        string $compareKey,
        $newValue,
        array $newAttributes,
        string $effectiveDate,
        string $sameValueMessage,
        ?callable $sameCheck = null
    ): void {
        $isSame = $sameCheck
            ? ($current && $sameCheck($current->{$compareKey}, $newValue))
            : ($current && (string) $current->{$compareKey} === (string) $newValue);

        if ($isSame) {
            abort(422, $sameValueMessage);
        }

        $current?->update(['end_date' => Carbon::parse($effectiveDate)->subDay()->toDateString()]);

        $relation->create($newAttributes + ['start_date' => $effectiveDate]);
    }

    private function workedSeconds($detail): int
    {
        if (!$detail->in_time || !$detail->out_time) {
            return 0;
        }

        return (int) abs($detail->in_time->diffInSeconds($detail->out_time));
    }

    private function formatSecondsAsTime(int $seconds): string
    {
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $secs = $seconds % 60;

        return sprintf('%d:%02d:%02d', $hours, $minutes, $secs);
    }

    /**
     * Expand holiday date ranges (global, or scoped to this department)
     * into a flat set of "Y-m-d" strings within the period.
     */
    private function holidayDates(Carbon $start, Carbon $end, ?int $departmentId): Collection
    {
        $dates = collect();

        Holiday::query()
            ->where('start_date', '<=', $end)
            ->where('end_date', '>=', $start)
            ->where(fn($q) => $q->whereNull('department_id')->orWhere('department_id', $departmentId))
            ->get()
            ->each(function (Holiday $holiday) use ($start, $end, &$dates) {
                $from = $holiday->start_date->max($start);
                $to = $holiday->end_date->min($end);

                for ($date = $from->copy(); $date->lte($to); $date->addDay()) {
                    $dates->push($date->toDateString());
                }
            });

        return $dates->unique();
    }

    /**
     * This employee's approved, paid-type leave requests.
     */
    private function approvedPaidLeaves(int $employeeId): Collection
    {
        return LeaveRequest::query()
            ->where('user_id', $employeeId)
            ->where('status', 'approved')
            ->whereHas('leaveType', fn($q) => $q->where('type', 'paid'))
            ->get();
    }

    /**
     * Whole-day paid leave (single/multi-day) expanded into a flat set of
     * "Y-m-d" strings within the period. "hours"-duration leave doesn't
     * replace a whole day, so it's excluded here (still counted in
     * paidLeaveSeconds() below).
     */
    private function paidLeaveDates(int $employeeId, Carbon $start, Carbon $end): Collection
    {
        $dates = collect();

        $this->approvedPaidLeaves($employeeId)->each(function (LeaveRequest $leave) use ($start, $end, &$dates) {
            if ($leave->leave_duration === 'multi_day' && $leave->start_date && $leave->end_date) {
                $from = $leave->start_date->max($start);
                $to = $leave->end_date->min($end);

                for ($date = $from->copy(); $date->lte($to); $date->addDay()) {
                    $dates->push($date->toDateString());
                }
            } elseif ($leave->leave_duration === 'single_day' && $leave->date?->between($start, $end)) {
                $dates->push($leave->date->toDateString());
            }
        });

        return $dates->unique();
    }

    /**
     * Approved, paid-type leave converted into seconds for the period —
     * "hours" leave uses its start/end time, single/multi-day leave uses the
     * employee's scheduled shift duration for each day taken.
     */
    private function paidLeaveSeconds(int $employeeId, Carbon $start, Carbon $end, ?WorkShift $shift): int
    {
        $seconds = 0;

        $this->approvedPaidLeaves($employeeId)->each(function (LeaveRequest $leave) use ($start, $end, $shift, &$seconds) {
            if ($leave->leave_duration === 'hours' && $leave->date?->between($start, $end) && $leave->start_time && $leave->end_time) {
                $seconds += (int) abs(Carbon::parse($leave->start_time)->diffInSeconds(Carbon::parse($leave->end_time)));

                return;
            }

            if ($leave->leave_duration === 'multi_day' && $leave->start_date && $leave->end_date) {
                $from = $leave->start_date->max($start);
                $to = $leave->end_date->min($end);

                for ($date = $from->copy(); $date->lte($to); $date->addDay()) {
                    $seconds += $this->scheduleService->scheduledSeconds($shift, $date);
                }
            } elseif ($leave->leave_duration === 'single_day' && $leave->date?->between($start, $end)) {
                $seconds += $this->scheduleService->scheduledSeconds($shift, $leave->date);
            }
        });

        return $seconds;
    }
}

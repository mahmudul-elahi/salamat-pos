<?php

namespace App\Http\Controllers\Hrm;

use App\Exceptions\GeneralException;
use App\Filters\Hrm\AttendanceFilter;
use App\Http\Controllers\Controller;
use App\Models\Customization\Customization;
use App\Models\Hrm\Attendance;
use App\Models\Hrm\AttendanceBreak;
use App\Models\Hrm\AttendanceDetail;
use App\Models\Hrm\BreakTime;
use App\Models\Hrm\Holiday;
use App\Models\Hrm\LeaveRequest;
use App\Models\Hrm\WorkShift;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class AttendanceController extends Controller
{
    public function index()
    {
        return Attendance::query()
            ->with([
                'user',
                'user.media',
                'user.employeeProfile',
                'workShift:id,shift_name',
                'details',
                'breaks',
            ])
            ->filters(new AttendanceFilter)

            ->orderByDesc('in_date')
            ->orderBy('user_id')
            ->paginate(request('per_page', 10))
            ->through(function (Attendance $attendance) {
                $details = $attendance->details->sortBy('in_time')->values();
                $firstIn = $details->first();
                $lastOut = $details->filter(fn (AttendanceDetail $d) => $d->out_time)->sortBy('out_time')->last();
                $totalSeconds = $details->sum(fn (AttendanceDetail $d) => $this->sessionSeconds($d));
                $breakSeconds = $attendance->breaks->sum(fn (AttendanceBreak $b) => $b->durationSeconds());

                return [
                    'id' => $attendance->id,
                    'employee' => $attendance->user?->name,
                    'profile_picture' => $attendance->user?->profile_picture,
                    'employee_code' => $attendance->user?->employeeProfile?->employee_id,
                    'email' => $attendance->user?->email,
                    'in_date' => to_user_timezone_date($attendance->in_date),
                    'records_count' => $details->count(),
                    'in_time' => $firstIn?->in_time ? to_user_timezone_datetime($firstIn->in_time, 'h:i A') : null,
                    'in_ip' => $this->extractIp($firstIn?->in_ip_data),
                    'in_location' => $this->extractLocationData($firstIn?->in_ip_data),
                    'in_selfie' => $firstIn?->in_selfie_url,
                    'out_time' => $lastOut?->out_time ? to_user_timezone_datetime($lastOut->out_time, 'h:i A') : null,
                    'out_ip' => $this->extractIp($lastOut?->out_ip_data),
                    'out_location' => $this->extractLocationData($lastOut?->out_ip_data),
                    'out_selfie' => $lastOut?->out_selfie_url,
                    'behavior' => $attendance->behavior,
                    'status' => $attendance->status,
                    'break_time' => $this->formatSeconds($breakSeconds),
                    'total_hours' => $this->formatSeconds($totalSeconds),
                ];
            });
    }

    /**
     * Monthly "Daily Attendance Record" grid: one row per employee, one
     * column per day of the month, each cell marked Present / Absent /
     * Holiday / Non-working day.
     *
     * Note: "Leave" is part of the legend but is always 0 for now — leave
     * requests aren't linked to an employee in this app yet, so there's no
     * way to attribute a leave day to a specific person.
     */
    public function report(Request $request)
    {
        $month = (int) $request->input('month', now()->month);
        $year = (int) $request->input('year', now()->year);
        $departmentId = $request->input('department_id');

        $daysInMonth = Carbon::createFromDate($year, $month, 1)->daysInMonth;
        $days = range(1, $daysInMonth); // plain day numbers, used for all internal logic below

        $employeesQuery = User::query()
            ->whereHas('employeeProfile')
            // Inactive employees stay off the report by default - but if
            // they were punching in during the month being viewed, they
            // still show for that month (e.g. someone deactivated mid-month
            // shouldn't vanish from their own attendance history).
            ->where(function ($query) use ($year, $month) {
                $query->where('status', 'active')
                    ->orWhereHas('attendances', fn ($q) => $q->whereYear('in_date', $year)->whereMonth('in_date', $month));
            })
            ->with(['media', 'employeeProfile', 'currentDepartment.department.workShifts', 'workShifts']);

        if ($departmentId) {
            $employeesQuery->whereHas('currentDepartment', fn ($q) => $q->where('department_id', $departmentId));
        }

        $employees = $employeesQuery->get();

        // Punch in/out is open to every logged-in user, not just formal
        // Employees, so also pull in anyone who actually punched this month
        // (e.g. an admin account without an employee profile) — otherwise
        // their attendance would silently never show up in this report.
        if (! $departmentId) {
            $punchedUserIds = Attendance::query()
                ->whereYear('in_date', $year)
                ->whereMonth('in_date', $month)
                ->whereNotIn('user_id', $employees->pluck('id'))
                ->pluck('user_id')
                ->unique();

            if ($punchedUserIds->isNotEmpty()) {
                $extraUsers = User::query()
                    ->whereIn('id', $punchedUserIds)
                    ->with(['media', 'employeeProfile', 'currentDepartment.department.workShifts', 'workShifts'])
                    ->get();

                $employees = $employees->merge($extraUsers);
            }
        }

        $attendancesByUser = Attendance::query()
            ->whereIn('user_id', $employees->pluck('id'))
            ->whereYear('in_date', $year)
            ->whereMonth('in_date', $month)
            ->whereNotIn('status', ['rejected', 'cancel'])
            ->get()
            ->groupBy('user_id');

        $holidays = Holiday::query()
            ->where('start_date', '<=', Carbon::createFromDate($year, $month, $daysInMonth))
            ->where('end_date', '>=', Carbon::createFromDate($year, $month, 1))
            ->get();

        $monthStart = Carbon::createFromDate($year, $month, 1);
        $monthEnd = Carbon::createFromDate($year, $month, $daysInMonth);

        $leaveRequestsByUser = LeaveRequest::query()
            ->whereIn('user_id', $employees->pluck('id'))
            ->where('status', 'approved')
            ->where(function ($query) use ($monthStart, $monthEnd) {
                $query->whereBetween('date', [$monthStart, $monthEnd])
                    ->orWhere(function ($query) use ($monthStart, $monthEnd) {
                        $query->where('start_date', '<=', $monthEnd)
                            ->where('end_date', '>=', $monthStart);
                    });
            })
            ->get()
            ->groupBy('user_id');

        $dailyTotals = array_fill_keys($days, 0);
        $summary = ['present' => 0, 'absent' => 0, 'leave' => 0, 'holiday' => 0, 'late' => 0, 'employees' => $employees->count()];

        $employeeRows = $employees->map(function (User $employee) use (
            $days,
            $year,
            $month,
            $daysInMonth,
            $attendancesByUser,
            $holidays,
            $leaveRequestsByUser,
            &$dailyTotals,
            &$summary
        ) {
            $department = $employee->currentDepartment?->department;
            $shift = $employee->workShifts->first() ?? $department?->workShifts?->first();
            $weekendDays = $shift?->weekend_days ?? [];
            $holidayDays = $this->holidayDaysInMonth($holidays, $department?->id, $year, $month, $daysInMonth);
            $leaveDays = $this->leaveDaysInMonth($leaveRequestsByUser->get($employee->id, collect()), $year, $month, $daysInMonth);
            $attendanceMap = collect($attendancesByUser->get($employee->id, collect()))
                ->keyBy(fn (Attendance $a) => $a->in_date->day);

            $totalPresent = 0;
            $dayStatuses = [];
            $lateDays = [];

            foreach ($days as $day) {
                $dayName = Carbon::createFromDate($year, $month, $day)->format('l');

                if ($attendanceMap->has($day)) {
                    $status = 'P';
                    $totalPresent++;
                    $dailyTotals[$day]++;
                    $summary['present']++;
                    if ($attendanceMap[$day]->behavior === 'late') {
                        $lateDays[] = $day;
                        $summary['late']++;
                    }
                } elseif (in_array($day, $holidayDays, true)) {
                    $status = 'H';
                    $summary['holiday']++;
                } elseif (in_array($dayName, $weekendDays, true)) {
                    $status = '-';
                } elseif (in_array($day, $leaveDays, true)) {
                    $status = 'L';
                    $summary['leave']++;
                } else {
                    $status = 'A';
                    $summary['absent']++;
                }

                $dayStatuses[$day] = $status;
            }

            return [
                'id' => $employee->id,
                'name' => $employee->name,
                'profile_picture' => $employee->profile_picture,
                'department' => $department?->name,
                'days' => $dayStatuses,
                'late_days' => $lateDays,
                'total_present' => $totalPresent,
            ];
        });

        // Build the {day, name} structure separately for the response,
        // so all the internal logic above keeps working with plain day numbers.
        $daysWithNames = collect($days)->map(fn ($day) => [
            'day' => $day,
            'name' => Carbon::createFromDate($year, $month, $day)->format('D'), // Mon, Tue, Wed...
        ]);

        return [
            'month' => $month,
            'year' => $year,
            'days' => $daysWithNames->values(),
            'employees' => $employeeRows->values(),
            'daily_totals' => $dailyTotals,
            'summary' => $summary,
        ];
    }

    /**
     * Expand the holiday date ranges that apply to this department (or all
     * departments, for org-wide holidays) into a flat list of day numbers
     * within the given month.
     */
    private function holidayDaysInMonth(Collection $holidays, ?int $departmentId, int $year, int $month, int $daysInMonth): array
    {
        $monthStart = Carbon::createFromDate($year, $month, 1);
        $monthEnd = Carbon::createFromDate($year, $month, $daysInMonth);
        $days = [];

        foreach ($holidays as $holiday) {
            if ($holiday->department_id && $holiday->department_id != $departmentId) {
                continue;
            }

            $start = $holiday->start_date->max($monthStart);
            $end = $holiday->end_date->min($monthEnd);

            if ($start->gt($end)) {
                continue;
            }

            for ($day = $start->day; $day <= $end->day; $day++) {
                $days[] = $day;
            }
        }

        return array_unique($days);
    }

    /**
     * Expand this employee's approved leave requests into a flat list of day
     * numbers within the given month — single_day/hours leaves use `date`,
     * multi_day leaves use the `start_date`/`end_date` range.
     */
    private function leaveDaysInMonth(Collection $leaveRequests, int $year, int $month, int $daysInMonth): array
    {
        $monthStart = Carbon::createFromDate($year, $month, 1);
        $monthEnd = Carbon::createFromDate($year, $month, $daysInMonth);
        $days = [];

        foreach ($leaveRequests as $leave) {
            if ($leave->leave_duration === 'multi_day' && $leave->start_date && $leave->end_date) {
                $start = $leave->start_date->max($monthStart);
                $end = $leave->end_date->min($monthEnd);

                if ($start->gt($end)) {
                    continue;
                }

                for ($day = $start->day; $day <= $end->day; $day++) {
                    $days[] = $day;
                }
            } elseif ($leave->date && $leave->date->between($monthStart, $monthEnd)) {
                $days[] = $leave->date->day;
            }
        }

        return array_unique($days);
    }

    /**
     * The full punch session breakdown for one attendance day, shown in the
     * "View Details" modal on the list page.
     */
    public function punchDetails(Attendance $attendance)
    {
        $attendance->load(['user:id,name,email', 'user.employeeProfile', 'details' => fn ($q) => $q->orderBy('in_time')]);

        $details = $attendance->details;
        $totalSeconds = $details->sum(fn (AttendanceDetail $d) => $this->sessionSeconds($d));
        $lastOut = $details->filter(fn (AttendanceDetail $d) => $d->out_time)->sortBy('out_time')->last();

        return [
            'employee' => $attendance->user?->name,
            'employee_code' => $attendance->user?->employeeProfile?->employee_id,
            'in_date' => $attendance->in_date ? to_user_timezone_date($attendance->in_date) : null,
            'records_count' => $details->count(),
            'first_in' => $details->first()?->in_time ? to_user_timezone_datetime($details->first()->in_time, 'h:i A') : null,
            'last_out' => $lastOut?->out_time ? to_user_timezone_datetime($lastOut->out_time, 'h:i A') : null,
            'total_worked' => $this->formatSeconds($totalSeconds),
            'sessions' => $details->values()->map(fn (AttendanceDetail $detail, int $index) => [
                'index' => $index + 1,
                'in_time' => $detail->in_time ? to_user_timezone_datetime($detail->in_time, 'h:i A') : null,
                'out_time' => $detail->out_time ? to_user_timezone_datetime($detail->out_time, 'h:i A') : null,
                'in_ip' => $this->extractIp($detail->in_ip_data),
                'in_location' => $this->extractLocationData($detail->in_ip_data),
                'in_selfie' => $detail->in_selfie_url,
                'out_ip' => $this->extractIp($detail->out_ip_data),
                'out_location' => $this->extractLocationData($detail->out_ip_data),
                'out_selfie' => $detail->out_selfie_url,
                'duration' => $this->formatSeconds($this->sessionSeconds($detail)),
                'work_from_home' => (bool) $detail->work_from_home,
            ]),
        ];
    }

    public function store(Request $request)
    {
        $validated = $this->validateRequest($request);

        Attendance::query()->create($validated);

        return successResponse('Attendance created successfully');
    }

    public function show(Attendance $attendance)
    {
        return $attendance->load(['user:id,name,email', 'workShift:id,shift_name', 'details']);
    }

    public function update(Request $request, Attendance $attendance)
    {
        // Quick status-only update (approve / reject / cancel) from the list actions.
        if ($request->has('status') && ! $request->has('user_id')) {
            $request->validate([
                'status' => ['required', Rule::in(['approved', 'rejected', 'cancel', 'pending'])],
            ]);

            $attendance->update(['status' => $request->get('status')]);

            return successResponse('Attendance status updated successfully');
        }

        $validated = $this->validateRequest($request);

        $attendance->update($validated);

        return successResponse('Attendance updated successfully');
    }

    public function destroy(Attendance $attendance)
    {
        $attendance->details()->delete();
        $attendance->delete();

        return successResponse('Attendance deleted successfully');
    }

    public function geoSettings(): array
    {
        $geoSettings = Customization::where('type', 'attendance')
            ->whereIn('name', ['geo_provider', 'mapbox_api_key', 'google_map_api_key', 'ip_geolocation_api_key'])
            ->get(['name', 'value'])
            ->pluck('value', 'name');

        $geoProvider = $geoSettings->get('geo_provider', 'none');
        $geoApiKey = match ($geoProvider) {
            'mapbox' => $geoSettings->get('mapbox_api_key'),
            'google_map' => $geoSettings->get('google_map_api_key'),
            'ip_geolocation' => $geoSettings->get('ip_geolocation_api_key'),
            default => null,
        };

        return ['geo_provider' => $geoProvider, 'geo_api_key' => $geoApiKey];
    }

    /**
     * The authenticated user's punch status for today, used by the navbar
     * punch in/out button.
     */
    public function today()
    {
        $user = auth()->user();

        $attendance = Attendance::query()
            ->where('user_id', $user->id)
            ->where('in_date', now('UTC')->toDateString())
            ->with(['details', 'breaks.breakTime', 'workShift.breakTimes'])
            ->first();

        $openDetail = $attendance?->details->firstWhere('out_time', null);
        $lastDetail = $attendance?->details->sortByDesc('in_time')->first();
        $openBreak = $attendance?->breaks->firstWhere('ended_at', null);
        $totalBreakSeconds = $attendance?->breaks
            ->sum(fn (AttendanceBreak $b) => $b->durationSeconds()) ?? 0;

        $availableBreakTimes = $attendance?->workShift?->breakTimes
            ?->map(fn (BreakTime $bt) => ['id' => $bt->id, 'name' => $bt->name, 'duration' => $bt->duration])
            ->values() ?? collect();

        $geoSettings = Customization::where('type', 'attendance')
            ->whereIn('name', ['geo_provider', 'mapbox_api_key', 'google_map_api_key', 'ip_geolocation_api_key'])
            ->get(['name', 'value'])
            ->pluck('value', 'name');

        $geoProvider = $geoSettings->get('geo_provider', 'none');
        $geoApiKey = match ($geoProvider) {
            'mapbox' => $geoSettings->get('mapbox_api_key'),
            'google_map' => $geoSettings->get('google_map_api_key'),
            'ip_geolocation' => $geoSettings->get('ip_geolocation_api_key'),
            default => null,
        };

        // Direct employee assignment takes priority over department shifts
        $directShifts = $user->workShifts()->select('id', 'shift_name')->get();
        $availableWorkShifts = $directShifts->isNotEmpty()
            ? $directShifts->map(fn (WorkShift $s) => ['id' => $s->id, 'name' => $s->shift_name])->values()
            : ($user->currentDepartment?->department?->workShifts()
                ->select('id', 'shift_name')
                ->get()
                ->map(fn (WorkShift $s) => ['id' => $s->id, 'name' => $s->shift_name])
                ->values() ?? collect());

        return [
            'punched_in' => (bool) $openDetail,
            'in_time' => $openDetail?->in_time ? to_user_timezone_datetime($openDetail->in_time, 'h:i A') : null,
            'in_date' => $attendance?->in_date ? to_user_timezone_date($attendance->in_date, 'Y-m-d') : null,
            'work_from_home' => (bool) ($openDetail?->work_from_home ?? $lastDetail?->work_from_home),
            'status' => $attendance?->status,
            'behavior' => $attendance?->behavior,
            'on_break' => (bool) $openBreak,
            'break_started_at' => $openBreak?->started_at ? to_user_timezone_datetime($openBreak->started_at, 'h:i A') : null,
            'break_name' => $openBreak?->breakTime?->name,
            'total_break' => $this->formatSeconds($totalBreakSeconds),
            'available_break_times' => $availableBreakTimes,
            'breaks_today' => $attendance?->breaks->map(fn (AttendanceBreak $b) => [
                'name' => $b->breakTime?->name,
                'started_at' => $b->started_at ? to_user_timezone_datetime($b->started_at, 'h:i A') : null,
                'ended_at' => $b->ended_at ? to_user_timezone_datetime($b->ended_at, 'h:i A') : null,
                'duration' => $this->formatSeconds($b->durationSeconds()),
            ])->values() ?? collect(),
            'geo_provider' => $geoProvider,
            'geo_api_key' => $geoApiKey,
            'available_work_shifts' => $availableWorkShifts,
        ];
    }

    /**
     * Punch in for the authenticated user. Creates today's attendance record
     * on the first punch of the day and opens a new punch session.
     */
    public function punchIn(Request $request)
    {
        $request->validate([
            'work_shift_id' => ['nullable', 'exists:work_shifts,id'],
            'work_from_home' => ['nullable', 'boolean'],
            'note' => ['nullable', 'string'],
            'ip' => ['nullable', 'string'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'address' => ['nullable', 'string'],
            'location_error' => ['nullable', 'string'],
            'selfie' => ['nullable', 'image', 'max:5120'],
        ]);

        $user = auth()->user();
        $now = now('UTC');

        $attendance = Attendance::query()->firstOrNew([
            'user_id' => $user->id,
            'in_date' => $now->toDateString(),
        ]);

        if (! $attendance->exists) {
            $workShiftId = $request->input('work_shift_id');

            if (! $workShiftId) {
                // 1. Direct employee assignment takes priority
                $directShift = $user->workShifts()->first();

                if ($directShift) {
                    $workShiftId = $directShift->id;
                } else {
                    // 2. Fall back to department's work shift (only if exactly one)
                    $deptShifts = $user->currentDepartment?->department?->workShifts;

                    if (! $deptShifts || $deptShifts->isEmpty()) {
                        throw new GeneralException('No work shift found for this employee. Please select a work shift.');
                    }

                    if ($deptShifts->count() > 1) {
                        throw new GeneralException('Multiple work shifts found. Please select a work shift.');
                    }

                    $workShiftId = $deptShifts->first()->id;
                }
            }

            $workShift = WorkShift::query()->with('schedules')->find($workShiftId);

            $attendance->work_shift_id = $workShift->id;
            $attendance->status = 'approved';
            $attendance->behavior = $this->calculateBehavior($workShift, $now);
            $attendance->save();
        }

        if ($attendance->details()->whereNull('out_time')->exists()) {
            throw new GeneralException('Already punched in');
        }

        $attendance->details()->create([
            'in_time' => $now,
            'added_by' => $user->id,
            'in_ip_data' => $this->buildIpData($request),
            'in_note' => $request->input('note'),
            'in_selfie' => $request->file('selfie')?->store("attendance/{$user->id}/in"),
            'work_from_home' => $request->boolean('work_from_home'),
        ]);

        return successResponse('Punched in successfully');
    }

    /**
     * Punch out the open session for the authenticated user's attendance today.
     */
    public function punchOut(Request $request)
    {
        $request->validate([
            'note' => ['nullable', 'string'],
            'ip' => ['nullable', 'string'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'address' => ['nullable', 'string'],
            'location_error' => ['nullable', 'string'],
            'selfie' => ['nullable', 'image', 'max:5120'],
        ]);

        $user = auth()->user();
        $now = now('UTC');

        $attendance = Attendance::query()
            ->where('user_id', $user->id)
            ->where('in_date', $now->toDateString())
            ->first();

        if (! $attendance) {
            throw new GeneralException("You haven't punched in today");
        }

        $detail = $attendance->details()->whereNull('out_time')->latest('in_time')->first();

        if (! $detail) {
            throw new GeneralException('You are not currently punched in');
        }

        // Auto-close any open break before punching out
        $attendance->breaks()->whereNull('ended_at')->update(['ended_at' => $now]);

        $detail->update([
            'out_time' => $now,
            'out_ip_data' => $this->buildIpData($request),
            'out_note' => $request->input('note'),
            'out_selfie' => $request->file('selfie')?->store("attendance/{$user->id}/out"),
        ]);

        return successResponse('Punched out successfully');
    }

    public function startBreak(Request $request)
    {
        $request->validate([
            'break_time_id' => ['required', 'exists:break_times,id'],
        ]);

        $user = auth()->user();
        $now = now('UTC');

        $attendance = Attendance::query()
            ->where('user_id', $user->id)
            ->where('in_date', $now->toDateString())
            ->first();

        if (! $attendance) {
            throw new GeneralException("You haven't punched in today");
        }

        if (! $attendance->details()->whereNull('out_time')->exists()) {
            throw new GeneralException('You are not currently punched in');
        }

        if ($attendance->breaks()->whereNull('ended_at')->exists()) {
            throw new GeneralException('You are already on a break');
        }

        $attendance->breaks()->create([
            'break_time_id' => $request->input('break_time_id'),
            'started_at' => $now,
        ]);

        return successResponse('Break started');
    }

    public function endBreak(Request $request)
    {
        $user = auth()->user();
        $now = now('UTC');

        $attendance = Attendance::query()
            ->where('user_id', $user->id)
            ->where('in_date', $now->toDateString())
            ->first();

        if (! $attendance) {
            throw new GeneralException("You haven't punched in today");
        }

        $openBreak = $attendance->breaks()->whereNull('ended_at')->latest('started_at')->first();

        if (! $openBreak) {
            throw new GeneralException('You are not on a break');
        }

        $openBreak->update(['ended_at' => $now]);

        return successResponse('Break ended');
    }

    /**
     * Combine the server-detected IP with whatever IP/location data the
     * client was able to collect, stored as JSON in the *_ip_data columns.
     */
    private function buildIpData(Request $request): string
    {
        $geoProvider = Customization::where('type', 'attendance')
            ->where('name', 'geo_provider')
            ->value('value') ?? 'none';

        return json_encode([
            'server_ip' => $request->ip(),
            'client_ip' => $request->input('ip'),
            'latitude' => $request->input('latitude'),
            'longitude' => $request->input('longitude'),
            'address' => $request->input('address'),
            'location_error' => $request->input('location_error'),
            'geo_provider' => $geoProvider,
        ]);
    }

    private function extractLocationData(?string $ipData): ?array
    {
        if (! $ipData) {
            return null;
        }

        $data = json_decode($ipData, true);

        if (! is_array($data)) {
            return $ipData ? ['ip' => $ipData] : null;
        }

        $ip = $data['client_ip'] ?? $data['server_ip'] ?? null;

        if (! $ip && ! ($data['latitude'] ?? null)) {
            return null;
        }

        return [
            'ip' => $ip,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'address' => $data['address'] ?? null,
            'geo_provider' => $data['geo_provider'] ?? null,
        ];
    }

    private function extractIp(?string $ipData): ?string
    {
        if (! $ipData) {
            return null;
        }

        $data = json_decode($ipData, true);

        if (is_array($data)) {
            return $data['client_ip'] ?? $data['server_ip'] ?? null;
        }

        return $ipData;
    }

    private function sessionSeconds(AttendanceDetail $detail): int
    {
        if (! $detail->in_time || ! $detail->out_time) {
            return 0;
        }

        return (int) abs($detail->in_time->diffInSeconds($detail->out_time));
    }

    private function formatSeconds(int $seconds): string
    {
        $h = intdiv($seconds, 3600);
        $m = intdiv($seconds % 3600, 60);
        $s = $seconds % 60;

        return sprintf('%d:%02d:%02d', $h, $m, $s);
    }

    private function calculateBehavior(WorkShift $shift, Carbon $punchTime): string
    {
        $tz = user_timezone();

        $localPunch = $punchTime->copy()->setTimezone($tz);

        $startTime = $shift->working_shift_type === 'scheduled'
            ? $shift->schedules->firstWhere('day_name', $localPunch->format('l'))?->start_time
            : $shift->start_time;

        if (! $startTime) {
            return 'regular';
        }

        $scheduledStart = Carbon::parse($localPunch->toDateString().' '.$startTime, $tz);

        if ($localPunch->lt($scheduledStart)) {
            return 'early';
        }

        $toleranceMinutes = (int) Customization::where('type', 'attendance')
            ->where('name', 'punch_in_tolerance')
            ->value('value');

        $toleranceEnd = $scheduledStart->copy()->addMinutes($toleranceMinutes);

        if ($localPunch->lte($toleranceEnd)) {
            return 'regular';
        }

        return 'late';
    }

    private function validateRequest(Request $request): array
    {
        return $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'work_shift_id' => ['required', 'exists:work_shifts,id'],
            'in_date' => ['required', 'date'],
            'behavior' => ['required', Rule::in(['early', 'late', 'regular'])],
            'status' => ['required', Rule::in(['approved', 'rejected', 'cancel', 'pending'])],
        ]);
    }
}

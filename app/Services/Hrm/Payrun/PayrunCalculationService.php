<?php

namespace App\Services\Hrm\Payrun;

use App\Models\Hrm\Attendance;
use App\Models\Hrm\BeneficiaryValue;
use App\Models\Hrm\Holiday;
use App\Models\Hrm\LeaveRequest;
use App\Models\Hrm\PayrunType;
use App\Models\Hrm\PayslipItem;
use App\Models\Hrm\WorkShift;
use App\Models\User;
use App\Services\Hrm\WorkShiftScheduleService;
use App\Services\Setting\SettingService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Works out who is eligible for a payrun and how much each of them should
 * be paid for a given period, following the "Default Payrun" rules:
 *
 *   1. Holidays are paid (not counted against the employee).
 *   2. Approved paid leave is paid (not counted against the employee).
 *   3. Beneficiary (allowance/deduction) defaults are applied on top of the
 *      earned basic salary.
 *   4. If the payrun type's consider_type is "none", the employee simply
 *      gets their full basic salary — no beneficiaries, no leave/holiday
 *      bookkeeping, no attendance-based deductions at all.
 *   5. Employees (or whole departments) listed in "Manage Audience" are
 *      skipped entirely.
 *
 * Note: per-employee overrides of the default payrun type/beneficiaries
 * (mentioned in the settings page copy as a future "Employees details"
 * feature) aren't implemented yet — every employee currently uses the one
 * org-wide default.
 */
class PayrunCalculationService
{
    private const string DEFAULT_BENEFICIARY_MODEL = 'default';

    private const int DEFAULT_BENEFICIARY_MODEL_ID = 0;

    /** Overtime is paid at this multiple of the employee's normal hourly rate. */
    private const float OVERTIME_MULTIPLIER = 1.5;

    public function __construct(
        protected SettingService $settingService,
        protected WorkShiftScheduleService $scheduleService,
    ) {}

    /**
     * The org-wide default payrun type, falling back to a sensible
     * in-memory default (not persisted) if none has been saved yet.
     */
    public function defaultPayrunType(): PayrunType
    {
        return PayrunType::query()->firstWhere('is_default', true) ?? new PayrunType([
            'name' => 'Default Payrun',
            'payrun_period' => 'monthly',
            'consider_type' => 'hour',
            'consider_overtime' => true,
            'is_default' => true,
        ]);
    }

    /**
     * The most recently completed period for the given payrun period type —
     * last calendar month, or last calendar week.
     */
    public function lastPeriodFor(PayrunType $payrunType): array
    {
        $now = now();

        if ($payrunType->payrun_period === 'weekly') {
            $start = $now->copy()->subWeek()->startOfWeek();
            $end = $now->copy()->subWeek()->endOfWeek();
        } else {
            $start = $now->copy()->subMonthNoOverflow()->startOfMonth();
            $end = $now->copy()->subMonthNoOverflow()->endOfMonth();
        }

        return [$start->startOfDay(), $end->startOfDay()];
    }

    /**
     * Employees eligible for the default payrun — every employee, minus
     * whoever (or whichever department) is listed in "Manage Audience".
     */
    public function eligibleEmployees(): Collection
    {
        [$restrictedDepartments, $restrictedEmployees] = $this->audienceRestrictions();

        return User::query()
            ->whereHas('employeeProfile')
            ->where('status', 'active')
            ->whereNotIn('id', $restrictedEmployees)
            ->when(! empty($restrictedDepartments), fn ($query) => $query->whereDoesntHave(
                'currentDepartment',
                fn ($q) => $q->whereIn('department_id', $restrictedDepartments)
            ))
            ->with(['employeeProfile', 'currentDepartment.department.workShifts.schedules', 'workShifts.schedules', 'currentSalary'])
            ->get();
    }

    /**
     * Employees eligible for a manual payrun.
     *
     * - If $employeeIds is non-empty: return exactly those employees (they were
     *   explicitly selected by the operator).
     * - Otherwise: return every active employee whose current department is one
     *   of $departmentIds.
     */
    public function eligibleEmployeesForManual(array $departmentIds, array $employeeIds): Collection
    {
        return User::query()
            ->whereHas('employeeProfile')
            ->where('status', 'active')
            ->when(
                $employeeIds !== [],
                fn ($q) => $q->whereIn('id', $employeeIds),
                fn ($q) => $q->when($departmentIds !== [], fn ($inner) => $inner->whereHas(
                    'currentDepartment',
                    fn ($d) => $d->whereIn('department_id', $departmentIds)
                ))
            )
            ->with(['employeeProfile', 'currentDepartment.department.workShifts.schedules', 'workShifts.schedules', 'currentSalary'])
            ->get();
    }

    /**
     * The default beneficiary values (allowances/deductions), grouped by
     * type, with their related Beneficiary preloaded.
     */
    public function defaultBeneficiaryValues(): Collection
    {
        return BeneficiaryValue::query()
            ->where('model_name', self::DEFAULT_BENEFICIARY_MODEL)
            ->where('model_id', self::DEFAULT_BENEFICIARY_MODEL_ID)
            ->whereHas('beneficiary', fn ($q) => $q->where('is_active', true))
            ->with('beneficiary:id,name,type')
            ->get();
    }

    /**
     * Calculate basic salary, net salary, and the line-item breakdown for
     * one employee over the given period.
     *
     * Returns null when the employee should be skipped entirely (no attendance
     * in an hour/daily_log payrun). The caller must check for null.
     *
     * @return array{basic_salary: float, net_salary: float, items: array}|null
     */
    public function calculateForEmployee(User $employee, Carbon $start, Carbon $end, PayrunType $payrunType, Collection $beneficiaryValues): ?array
    {
        $basicSalary = round((float) ($employee->currentSalary?->amount ?? 0), 2);

        if ($payrunType->consider_type === 'none') {
            return [
                'basic_salary' => $basicSalary,
                'net_salary' => $basicSalary,
                'items' => [
                    ['type' => PayslipItem::TYPE_BASIC, 'label' => 'Basic Salary', 'amount' => $basicSalary, 'beneficiary_id' => null],
                ],
            ];
        }

        // hour / daily_log: skip entirely if the employee had no attendance in
        // the period — avoids generating a zero-salary payslip.
        $hasAttendance = Attendance::query()
            ->where('user_id', $employee->id)
            ->whereBetween('in_date', [$start->toDateString(), $end->toDateString()])
            ->whereNotIn('status', ['rejected', 'cancel'])
            ->exists();

        if (! $hasAttendance) {
            return null;
        }

        [$earnedBasic, $overtimeAmount] = $this->calculateAttendanceBasedEarning($employee, $start, $end, $basicSalary, $payrunType);

        $items = [
            ['type' => PayslipItem::TYPE_BASIC, 'label' => 'Basic Salary (Earned)', 'amount' => round($earnedBasic, 2), 'beneficiary_id' => null],
        ];

        if ($overtimeAmount > 0) {
            $items[] = ['type' => PayslipItem::TYPE_EARNING, 'label' => 'Overtime', 'amount' => round($overtimeAmount, 2), 'beneficiary_id' => null];
        }

        $netSalary = $earnedBasic + $overtimeAmount;

        foreach ($beneficiaryValues as $value) {
            $amount = $value->is_percentage
                ? round($earnedBasic * $value->amount / 100, 2)
                : round((float) $value->amount, 2);

            $isAllowance = $value->beneficiary->type === 'allowance';
            $netSalary += $isAllowance ? $amount : -$amount;

            $items[] = [
                'type' => $isAllowance ? PayslipItem::TYPE_EARNING : PayslipItem::TYPE_DEDUCTION,
                'label' => $value->beneficiary->name,
                'amount' => $amount,
                'beneficiary_id' => $value->beneficiary_id,
            ];
        }

        return [
            'basic_salary' => $basicSalary,
            'net_salary' => round(max($netSalary, 0), 2),
            'items' => $items,
        ];
    }

    /**
     * Walk every day in the period and work out how much of the basic
     * salary was actually earned, plus any overtime pay.
     *
     * Holidays, weekends, and approved paid leave are always paid in full
     * (they don't reduce earned pay). On a normal working day:
     *  - "daily_log": full day rate if there's any attendance that day, 0 if absent.
     *  - "hour": day rate scaled by (hours worked / scheduled shift hours).
     */
    private function calculateAttendanceBasedEarning(User $employee, Carbon $start, Carbon $end, float $basicSalary, PayrunType $payrunType): array
    {
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
            ->keyBy(fn (Attendance $a) => $a->in_date->toDateString());

        $considerHours = $payrunType->consider_type === 'hour';

        // ── hour mode: total-period calculation ───────────────────────────
        // earned = (totalWorkedMinutes / totalScheduledWorkingMinutes) × salary
        // All attendance (even on weekend/holiday days) counts toward the
        // numerator; only non-weekend/holiday working days contribute to the
        // scheduled denominator.
        if ($considerHours) {
            return $this->calculateHourMode($start, $end, $basicSalary, $shift, $weekendDays, $holidayDates, $paidLeaveDates, $attendanceByDate, $payrunType);
        }

        // ── daily_log mode: per-day calculation ───────────────────────────
        // dayRate  = salary / total days in period.
        // Weekend / holiday / leave days are always paid (auto-paid dayRate).
        // Working days are paid only when the employee punched in.
        // Overtime = actual minutes worked beyond the scheduled shift on a
        //            working day (when consider_overtime is enabled).
        $totalDays = $start->copy()->startOfDay()->diffInDays($end->copy()->startOfDay()) + 1;
        $dayRate = $totalDays > 0 ? $basicSalary / $totalDays : 0;

        $earned = 0.0;
        $overtime = 0.0;
        $overtimeMinutes = 0;

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $dateKey = $date->toDateString();
            $isNonWorking = $holidayDates->contains($dateKey)
                || $paidLeaveDates->contains($dateKey)
                || in_array($date->format('l'), $weekendDays, true);

            if ($isNonWorking) {
                // Weekend / holiday / leave → always paid regardless of attendance.
                $earned += $dayRate;

                continue;
            }

            // Working day → paid only if punched in.
            $attendance = $attendanceByDate->get($dateKey);
            if (! $attendance) {
                continue;
            }

            $earned += $dayRate;

            if ($payrunType->consider_overtime) {
                $scheduledMinutes = $this->scheduledMinutesFor($shift, $date);
                $workedMinutes = $this->workedMinutesFor($attendance);
                if ($scheduledMinutes > 0 && $workedMinutes > $scheduledMinutes) {
                    $overtimeMinutes += $workedMinutes - $scheduledMinutes;
                }
            }
        }

        if ($payrunType->consider_overtime && $overtimeMinutes > 0 && $dayRate > 0) {
            $shiftMinutes = $this->scheduledMinutesFor($shift, $start);
            $hourlyRate = $shiftMinutes > 0 ? ($dayRate / $shiftMinutes) * 60 : 0;
            $overtime = ($overtimeMinutes / 60) * $hourlyRate * self::OVERTIME_MULTIPLIER;
        }

        return [$earned, $overtime];
    }

    /**
     * Hour-based payrun:
     *  - totalScheduledMinutes = scheduled minutes for non-weekend, non-holiday, non-leave days only.
     *  - totalWorkedMinutes    = all attendance including weekend/holiday/leave days.
     *  - earned = (workedMinutes / scheduledMinutes) × basicSalary  (capped at basicSalary).
     *  - Overtime = minutes worked beyond total scheduled, paid at 1.5× hourly rate.
     *
     * Working on a weekend/holiday still counts toward pay because those extra
     * hours increase the numerator of the ratio.
     */
    private function calculateHourMode(Carbon $start, Carbon $end, float $basicSalary, ?WorkShift $shift, array $weekendDays, Collection $holidayDates, Collection $paidLeaveDates, Collection $attendanceByDate, PayrunType $payrunType): array
    {
        $totalScheduledMinutes = 0;
        $totalWorkedMinutes = 0;

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $dateKey = $date->toDateString();
            $scheduledMin = $this->scheduledMinutesFor($shift, $date);
            $isNonWorking = $holidayDates->contains($dateKey)
                || $paidLeaveDates->contains($dateKey)
                || in_array($date->format('l'), $weekendDays, true);

            $totalScheduledMinutes += $scheduledMin;

            if ($isNonWorking) {
                // Non-working days are always fully paid — add scheduled hours automatically.
                $totalWorkedMinutes += $scheduledMin;
            } else {
                // Working days: count actual attended minutes.
                $attendance = $attendanceByDate->get($dateKey);
                if ($attendance) {
                    $totalWorkedMinutes += $this->workedMinutesFor($attendance);
                }
            }
        }

        $workingEarning = 0.0;
        $overtime = 0.0;

        if ($totalScheduledMinutes > 0) {
            $cappedWorked = min($totalWorkedMinutes, $totalScheduledMinutes);
            $workingEarning = ($cappedWorked / $totalScheduledMinutes) * $basicSalary;

            if ($payrunType->consider_overtime && $totalWorkedMinutes > $totalScheduledMinutes) {
                $hourlyRate = ($basicSalary / $totalScheduledMinutes) * 60;
                $overtimeHours = ($totalWorkedMinutes - $totalScheduledMinutes) / 60;
                $overtime = $overtimeHours * $hourlyRate * self::OVERTIME_MULTIPLIER;
            }
        }

        return [$workingEarning, $overtime];
    }

    private function workedMinutesFor(Attendance $attendance): int
    {
        return $attendance->details->sum(function ($detail) {
            if (! $detail->in_time || ! $detail->out_time) {
                return 0;
            }

            // Carbon 3's diffIn* methods return a signed value by default
            // (negative if called in the "wrong" direction) — abs() it so
            // we don't depend on argument order.
            return (int) abs($detail->in_time->diffInMinutes($detail->out_time));
        });
    }

    private function scheduledMinutesFor(?WorkShift $shift, Carbon $date): int
    {
        return $this->scheduleService->scheduledMinutes($shift, $date);
    }

    /**
     * Expand holiday date ranges (global, or scoped to this department) into
     * a flat set of "Y-m-d" strings within the period.
     */
    private function holidayDates(Carbon $start, Carbon $end, ?int $departmentId): Collection
    {
        $dates = collect();

        Holiday::query()
            ->where('start_date', '<=', $end)
            ->where('end_date', '>=', $start)
            ->where(fn ($q) => $q->whereNull('department_id')->orWhere('department_id', $departmentId))
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
     * Expand this employee's approved, paid-type leave requests into a flat
     * set of "Y-m-d" strings within the period.
     */
    private function paidLeaveDates(int $employeeId, Carbon $start, Carbon $end): Collection
    {
        $dates = collect();

        LeaveRequest::query()
            ->where('user_id', $employeeId)
            ->where('status', 'approved')
            ->whereHas('leaveType', fn ($q) => $q->where('type', 'paid'))
            ->get()
            ->each(function (LeaveRequest $leave) use ($start, $end, &$dates) {
                if ($leave->leave_duration === 'multi_day' && $leave->start_date && $leave->end_date) {
                    $from = $leave->start_date->max($start);
                    $to = $leave->end_date->min($end);

                    for ($date = $from->copy(); $date->lte($to); $date->addDay()) {
                        $dates->push($date->toDateString());
                    }
                } elseif ($leave->date && $leave->date->between($start, $end)) {
                    $dates->push($leave->date->toDateString());
                }
            });

        return $dates->unique();
    }

    /**
     * @return array{0: array<int>, 1: array<int>} [restrictedDepartmentIds, restrictedEmployeeIds]
     */
    private function audienceRestrictions(): array
    {
        $settings = $this->settingService->getFormattedSettings('payrun');

        $departments = isset($settings['restricted_departments']) ? json_decode($settings['restricted_departments'], true) : [];
        $employees = isset($settings['restricted_employees']) ? json_decode($settings['restricted_employees'], true) : [];

        return [$departments ?: [], $employees ?: []];
    }
}

<?php

namespace App\Jobs\Hrm;

use App\Models\Hrm\EmployeeLeave;
use App\Models\Hrm\LeaveType;
use App\Models\User;
use App\Services\Setting\SettingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Assigns (or refreshes) leave entitlements for all eligible employees.
 *
 * Rules:
 *  - The current leave year is derived from the `start_month` setting, and is
 *    handed out one month at a time: `leave_types.day` is a *per month*
 *    entitlement, so each employee gets one row per month of the year.
 *  - Only months that have already started are assigned, so running the job
 *    mid-year backfills the months gone by and stops at the current one.
 *  - Paid leave types go to employees whose active status is in
 *    `statuses_for_paid_leave`; unpaid leave types to
 *    `statuses_for_unpaid_leave`. If a list is empty every active employee
 *    is eligible for that leave type category.
 *  - Records already manually modified (`is_updated = true`) are preserved.
 */
class AssignEmployeeLeavesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function handle(SettingService $settingService): void
    {
        // ── Settings ──────────────────────────────────────────────────────
        $raw = $settingService->getFormattedSettings('leave');

        $startMonth      = (int) ($raw['start_month'] ?? 1);
        $paidStatusIds   = array_map('intval', json_decode($raw['statuses_for_paid_leave']   ?? '[]', true) ?: []);
        $unpaidStatusIds = array_map('intval', json_decode($raw['statuses_for_unpaid_leave'] ?? '[]', true) ?: []);

        // ── Leave year window ─────────────────────────────────────────────
        $today     = now();
        $startYear = $today->month >= $startMonth ? $today->year : $today->year - 1;
        $startDate = Carbon::createFromDate($startYear, $startMonth, 1)->startOfMonth();
        $endDate   = $startDate->copy()->addYear()->subDay();

        // Months of this leave year that have already begun — the current month
        // included, so the entitlement lands on day one of each month.
        $months = [];
        for ($month = $startDate->copy(); $month->lte($today) && $month->lte($endDate); $month->addMonth()) {
            $months[] = $month->copy();
        }

        if (empty($months)) {
            Log::info('AssignEmployeeLeavesJob skipped — leave year has not started yet.', [
                'leave_year' => $startDate->toDateString() . ' – ' . $endDate->toDateString(),
            ]);

            return;
        }

        // ── Data ──────────────────────────────────────────────────────────
        $leaveTypes = LeaveType::where('enable', true)->get();

        $employees = User::query()
            ->whereHas('currentEmployeeStatus')
            ->with('currentEmployeeStatus')
            ->where('status', 'active')
            ->get();

        // Everything already on the books for this leave year, so the loop below
        // never overwrites a row an admin has since edited (is_updated = true).
        $existing = EmployeeLeave::query()
            ->whereBetween('start_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->get(['employee_id', 'leave_type_id', 'start_date'])
            ->map(fn(EmployeeLeave $el) => $el->employee_id . ':' . $el->leave_type_id . ':' . $el->start_date->toDateString())
            ->flip();

        // ── Assign ────────────────────────────────────────────────────────
        $rows = [];
        $now  = now();

        foreach ($employees as $employee) {
            $statusId = (int) $employee->currentEmployeeStatus?->employee_status_id;

            foreach ($leaveTypes as $leaveType) {
                if ($leaveType->type === 'paid') {
                    $eligible = empty($paidStatusIds) || in_array($statusId, $paidStatusIds, true);
                } else {
                    $eligible = empty($unpaidStatusIds) || in_array($statusId, $unpaidStatusIds, true);
                }

                if (! $eligible) {
                    continue;
                }

                foreach ($months as $month) {
                    $monthStart = $month->toDateString();

                    if ($existing->has($employee->id . ':' . $leaveType->id . ':' . $monthStart)) {
                        continue;
                    }

                    $rows[] = [
                        'employee_id'   => $employee->id,
                        'leave_type_id' => $leaveType->id,
                        'start_date'    => $monthStart,
                        'end_date'      => $month->copy()->endOfMonth()->toDateString(),
                        'day'           => $leaveType->day,
                        'is_updated'    => false,
                        'created_at'    => $now,
                        'updated_at'    => $now,
                    ];
                }
            }
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            EmployeeLeave::insert($chunk);
        }

        Log::info('AssignEmployeeLeavesJob completed.', [
            'leave_year'  => $startDate->toDateString() . ' – ' . $endDate->toDateString(),
            'months'      => count($months),
            'employees'   => $employees->count(),
            'leave_types' => $leaveTypes->count(),
            'assigned'    => count($rows),
        ]);
    }

    public function failed(Throwable $e): void
    {
        Log::error('AssignEmployeeLeavesJob failed: ' . $e->getMessage());
    }
}

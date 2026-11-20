<?php

namespace App\Services\Hrm;

use App\Models\Hrm\WorkShift;
use Illuminate\Support\Carbon;

/**
 * Resolves how many seconds a work shift is scheduled for on a given date —
 * shared by the payrun calculator, the attendance behavior check, and the
 * employee attendance-stats endpoint so the "what hours is this shift on
 * this day" logic only lives in one place.
 */
class WorkShiftScheduleService
{
    public function scheduledSeconds(?WorkShift $shift, Carbon $date): int
    {
        if (! $shift instanceof WorkShift) {
            return 0;
        }

        if ($shift->working_shift_type === 'scheduled') {
            $schedule = $shift->schedules->firstWhere('day_name', $date->format('l'));
            $start = $schedule?->start_time;
            $end = $schedule?->end_time;
        } else {
            $start = $shift->start_time;
            $end = $shift->end_time;
        }

        if (! $start || ! $end) {
            return 0;
        }

        // Carbon 3's diffIn* methods return a signed value by default —
        // abs() it so we don't depend on argument order.
        return (int) abs(Carbon::parse($start)->diffInSeconds(Carbon::parse($end)));
    }

    public function scheduledMinutes(?WorkShift $shift, Carbon $date): int
    {
        return intdiv($this->scheduledSeconds($shift, $date), 60);
    }
}

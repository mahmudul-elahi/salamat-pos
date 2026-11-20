<?php

namespace App\Filters\Hrm;

use App\Filters\BaseFilter;
use Illuminate\Support\Carbon;

class AttendanceFilter extends BaseFilter
{
    public function userId($userId = null): void
    {
        $this->builder->when($userId, fn($q) => $q->where('user_id', $userId));
    }

    public function search($search = null): void
    {
        $this->builder->when($search, fn($q) => $q->whereHas('user', fn($uq) => $uq
            ->withTrashed()
            ->where('name', 'LIKE', "%{$search}%")
            ->orWhere('email', 'LIKE', "%{$search}%")
            ->orWhereHas('employeeProfile', fn($eq) => $eq->where('employee_id', 'LIKE', "%{$search}%"))
        ));
    }

    public function date($date = null): void
    {
        if (empty($date)) {
            return;
        }

        // Datatable date-range filter sends JSON string or plain array
        if (is_string($date)) {
            $decoded = json_decode($date, true);
            if (is_array($decoded)) {
                $date = $decoded;
            }
        }

        if (is_array($date)) {
            if (count($date) < 2 || empty($date[0]) || empty($date[1])) {
                return;
            }
            $this->builder->whereBetween('in_date', [
                Carbon::parse($date[0])->startOfDay(),
                Carbon::parse($date[1])->endOfDay(),
            ]);
        } else {
            // Single date string e.g. "2026-06-29" from Daily Log page
            $this->builder->whereDate('in_date', $date);
        }
    }

    public function month($month = null): void
    {
        $this->builder->when($month, fn($q) => $q->whereMonth('in_date', $month));
    }

    public function year($year = null): void
    {
        $this->builder->when($year, fn($q) => $q->whereYear('in_date', $year));
    }
}

<?php

namespace App\Helpers\Traits;

trait DateRangeHelper
{
    private function total(): array
    {
        return [
            'name' => 'total',
            'range' => [],
            'context' => 'Y'
        ];
    }

    private function last7Days(): array
    {
        return [
            'name' => 'last_7_days',
            'range' => [
                today()->subDays(6)->toDateTimeString(), now()->toDateTimeString()
            ],
            'context' => 'D'
        ];
    }

    private function thisWeek(): array
    {
        return [
            'name' => 'this_week',
            'range' => [
                now()->startOfWeek()->toDateTimeString(),
                now()->endOfWeek()->toDateTimeString(),
            ],
            'context' => 'D'
        ];
    }

    private function lastWeek(): array
    {
        return [
            'name' => 'last_week',
            'range' => [
                now()->subWeek()->startOfWeek()->toDateTimeString(),
                now()->subWeek()->endOfWeek()->toDateTimeString()
            ],
            'context' => 'D'
        ];
    }

    private function thisMonth(): array
    {
        return [
            'name' => 'this_month',
            'range' => [
                now()->startOfMonth()->toDateTimeString(),
                now()->endOfMonth()->toDateTimeString()
            ],
            'context' => 'Y-m-d'
        ];
    }

    private function lastMonth(): array
    {
        return [
            'name' => 'last_month',
            'range' => [
                now()->subMonth()->startOfMonth()->toDateTimeString(),
                now()->subMonth()->endOfMonth()->lastOfMonth()->toDateTimeString()
            ],
            'context' => 'Y-m-d'
        ];
    }

    private function thisYear(): array
    {
        return [
            'name' => 'this_year',
            'range' => [
                now()->startOfYear()->toDateTimeString(),
                now()->endOfYear()->toDateTimeString()
            ],
            'context' => 'M'
        ];
    }

    private function lastYear(): array
    {
        return [
            'name' => 'last_year',
            'range' => [
                now()->subYear()->startOfYear()->toDateTimeString(),
                now()->subYear()->endOfYear()->toDateTimeString()
            ],
            'context' => 'M'
        ];
    }

    public function dateRange($type): object
    {

        if ($type > 7) {
            $type = 0;
        }
        $context = [
            '0' => 'total',
            '1' => 'last7Days',
            '2' => 'thisWeek',
            '3' => 'lastWeek',
            '4' => 'thisMonth',
            '5' => 'lastMonth',
            '6' => 'thisYear',
            '7' => 'lastYear',
        ][$type];

        return (object)$this->$context();
    }

}

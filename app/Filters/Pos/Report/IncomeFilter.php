<?php

namespace App\Filters\Pos\Report;

use App\Exceptions\GeneralException;
use App\Filters\BaseFilter;
use Illuminate\Support\Carbon;

class IncomeFilter extends BaseFilter
{
    public function search($search = null): void
    {
        $this->builder->when($search, function ($builder) use ($search) {
            $builder->where('title', 'LIKE', "%$search%");

            // The invoice number shown on screen ("WTH-{id}") is synthetic
            // (built in the controller, not a real column), so searching
            // for it — e.g. "WTH-42" or just "42" — never matched anything.
            // Strip any non-digit prefix and match the remainder against id.
            $numeric = preg_replace('/^\D+/', '', trim($search));
            if ($numeric !== '' && is_numeric($numeric)) {
                $builder->orWhere('id', (int) $numeric);
            }
        });
    }

    /**
     * @throws GeneralException
     */
    public function date($range = null)
    {
        if (empty($range)) {
            return $this->builder;
        }

        // Handle JSON string input from query params
        if (is_string($range)) {
            $range = json_decode($range, true);
        }

        if (! is_array($range) || count($range) < 2) {
            return $this->builder->whereDate('date', Carbon::today());
        }

        [$startDate, $endDate] = $range;

        try {
            $start = Carbon::parse($startDate)->startOfDay();
            $end = Carbon::parse($endDate)->endOfDay();

            return $this->builder->whereBetween('date', [$start, $end]);
        } catch (\Exception) {
            throw new GeneralException('Invalid your date range');
        }
    }

    public function category($ids = null): void
    {
        if (! $ids) {
            return;
        }
        $ids = explode(',', $ids);
        $this->builder->whereIn('income_category_id', $ids);
    }

    public function shop($shopId = null): void
    {
        if (! $shopId) {
            return;
        }
        $this->builder->where('tenant_id', $shopId);
    }

    public function warehouse($shopId = null): void
    {
        if (! $shopId) {
            return;
        }
        $this->builder->where('tenant_id', $shopId);
    }

    public function director($ids = null): void
    {
        if (! $ids) {
            return;
        }
        $ids = explode(',', $ids);
        $this->builder->whereIn('user_id', $ids);
    }

    public function party($ids = null): void
    {
        if (! $ids) {
            return;
        }
        $ids = explode(',', $ids);
        $this->builder->whereIn('contact_id', $ids);
    }

    public function createdBy($ids = null): void
    {
        if (! $ids) {
            return;
        }
        $ids = explode(',', $ids);
        $this->builder->whereIn('created_by', $ids);
    }
}

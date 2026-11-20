<?php

namespace App\Filters\Pos\Report;

use App\Exceptions\GeneralException;
use App\Filters\BaseFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class DepositReportFilter extends BaseFilter
{

    public function search($search = null): void
    {
        $this->builder->when($search, function ($query, $search) {
            $query->where('title', 'like', '%' . $search . '%');

            // The invoice number shown on screen ("INV-{id}") is synthetic
            // (built in the controller, not a real column), so searching
            // for it — e.g. "INV-42" or just "42" — never matched anything.
            // Strip any non-digit prefix and match the remainder against id.
            $numeric = preg_replace('/^\D+/', '', trim($search));
            if ($numeric !== '' && is_numeric($numeric)) {
                $query->orWhere('id', (int) $numeric);
            }
        });
    }

    public function warehouse($ids = null): void
    {
        if (!$ids) return;
        $ids = explode(',', $ids);

        $this->builder->whereIn('tenant_id', $ids);
    }

    public function category($categoryIds = null): void
    {
        if (!$categoryIds) return;
        $categoryIds = explode(',', $categoryIds);
        $this->builder->whereIn('investment_category_id', $categoryIds);
    }

    public function director($ids = null): void
    {
        if (!$ids) return;
        $ids = explode(',', $ids);
        $this->builder->whereIn('user_id', $ids);
    }

    public function party($ids = null): void
    {
        if (!$ids) return;
        $ids = explode(',', $ids);
        $this->builder->whereIn('contact_id', $ids);
    }

    public function createdBy($ids = null): void
    {
        if (!$ids) return;
        $ids = explode(',', $ids);
        $this->builder->whereIn('created_by', $ids);
    }

    /**
     * @throws GeneralException
     */
    public function date($range = null): Builder|static
    {

        if (empty($range)) {
            return $this;
        }
        if (is_string($range)) {
            $range = json_decode($range, true);
        }

        if (!is_array($range) || count($range) < 2) {
            return $this;
        }

        [$startDate, $endDate] = $range;

        try {
            $start = Carbon::parse($startDate)->startOfDay();
            $end = Carbon::parse($endDate)->endOfDay();
            return $this->builder->whereBetween('date', [$start, $end]);
        } catch (\Exception $e) {
            throw new GeneralException('Invalid date range');
        }
    }
}

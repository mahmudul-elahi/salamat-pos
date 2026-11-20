<?php

namespace App\Filters\Pos\Report;

use App\Exceptions\GeneralException;
use App\Filters\BaseFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class DamageProductReportFilter extends BaseFilter
{
    public function search($search = null): void
    {
        $this->builder->when($search, function ($query, $search) {
            $query->where($query->getModel()->getTable() . '.reference_no', 'like', '%' . $search . '%');
        });
    }

    public function warehouse($ids = null): void
    {
        $warehouseIds = is_array($ids) ? $ids : explode(',', (string) $ids);

        $this->builder->when($ids, function (Builder $query) use ($warehouseIds) {
            $query->whereIn($query->getModel()->getTable() . '.tenant_id', $warehouseIds);
        });
    }

    public function status($statuses = null): void
    {
        $statusList = is_array($statuses) ? $statuses : explode(',', (string) $statuses);

        $this->builder->when($statuses, function (Builder $query) use ($statusList) {
            $query->whereIn($query->getModel()->getTable() . '.status', $statusList);
        });
    }

    public function createdBy($ids = null): void
    {
        $createdByIds = is_array($ids) ? $ids : explode(',', (string) $ids);

        $this->builder->when($ids, function (Builder $query) use ($createdByIds) {
            $query->whereIn($query->getModel()->getTable() . '.created_by', $createdByIds);
        });
    }

    public function approvedBy($ids = null): void
    {
        $approvedByIds = is_array($ids) ? $ids : explode(',', (string) $ids);

        $this->builder->when($ids, function (Builder $query) use ($approvedByIds) {
            $query->whereIn($query->getModel()->getTable() . '.approved_by', $approvedByIds);
        });
    }

    /**
     * @throws GeneralException
     */
    public function date($range = null)
    {
        if (empty($range)) {
            return;
        }

        if (is_string($range)) {
            $range = json_decode($range, true);
        }

        if (!is_array($range) || count($range) < 2) {
            return;
        }

        [$startDate, $endDate] = $range;

        try {
            $start = Carbon::parse($startDate)->startOfDay();
            $end = Carbon::parse($endDate)->endOfDay();
            $table = $this->builder->getModel()->getTable();

            return $this->builder->whereBetween($table . '.damage_date', [$start, $end]);
        } catch (\Exception $e) {
            throw new GeneralException('Invalid date range');
        }
    }
}

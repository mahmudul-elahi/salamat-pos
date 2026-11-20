<?php

namespace App\Filters\Pos\Report;

use App\Exceptions\GeneralException;
use App\Filters\BaseFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class SalesReportFilter extends BaseFilter
{
    public function search($search = null): void
    {
        $this->builder->when($search, function ($query, $search) {
            $table = $query->getModel()->getTable();
            $query->where($table.'.full_invoice_number', 'like', '%'.$search.'%')
                ->orWhereHas('customer', function ($query) use ($search) {
                    $query->where('name', 'like', '%'.$search.'%');
                });
        });
    }

    public function customer($ids = null): void
    {
        if (is_array($ids)) {
            $customerIds = $ids;
        } else {
            $customerIds = explode(',', $ids);
        }
        $this->builder->when($ids, function (Builder $query) use ($customerIds) {
            $query->whereIn($query->getModel()->getTable().'.contact_id', $customerIds);
        });
    }

    public function warehouse($ids = null): void
    {
        if (is_array($ids)) {
            $warehouseIds = $ids;
        } else {
            $warehouseIds = explode(',', $ids);
        }
        $this->builder->when($ids, function (Builder $query) use ($warehouseIds) {
            $query->whereIn($query->getModel()->getTable().'.tenant_id', $warehouseIds);
        });
    }

    public function seller($ids = null): void
    {
        if (is_array($ids)) {
            $sellerIds = $ids;
        } else {
            $sellerIds = explode(',', $ids);
        }
        $this->builder->when($ids, function (Builder $query) use ($sellerIds) {
            $query->whereIn($query->getModel()->getTable().'.seller_id', $sellerIds);
        });
    }

    public function createdBy($ids = null): void
    {
        if (is_array($ids)) {
            $createdByIds = $ids;
        } else {
            $createdByIds = explode(',', $ids);
        }
        $this->builder->when($ids, function (Builder $query) use ($createdByIds) {
            $query->whereIn($query->getModel()->getTable().'.created_by', $createdByIds);
        });
    }

    public function status($statuses = null): void
    {
        if (is_array($statuses)) {
            $statusList = $statuses;
        } else {
            $statusList = explode(',', $statuses);
        }
        $this->builder->when($statuses, function (Builder $query) use ($statusList) {
            $query->whereIn($query->getModel()->getTable().'.status', $statusList);
        });
    }

    public function paymentMethod($ids = null): void
    {
        if (is_array($ids)) {
            $paymentMethodIds = $ids;
        } else {
            $paymentMethodIds = explode(',', $ids);
        }
        $this->builder->when($ids, function (Builder $query) use ($paymentMethodIds) {
            $query->whereHas('payments', function ($paymentQuery) use ($paymentMethodIds) {
                $paymentQuery->whereIn('payment_method_id', $paymentMethodIds);
            });
        });
    }

    public function brand($ids = null): void
    {
        if (is_array($ids)) {
            $brandIds = $ids;
        } else {
            $brandIds = explode(',', $ids);
        }
        $this->builder->when($ids, function (Builder $query) use ($brandIds) {
            $query->whereHas('items.product', function ($productQuery) use ($brandIds) {
                $productQuery->whereIn('brand_id', $brandIds);
            });
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

        if (! is_array($range) || count($range) < 2) {
            return;
        }

        [$startDate, $endDate] = $range;

        try {
            $start = Carbon::parse($startDate)->startOfDay();
            $end = Carbon::parse($endDate)->endOfDay();
            $table = $this->builder->getModel()->getTable();

            return $this->builder->whereBetween($table.'.date', [$start, $end]);
        } catch (\Exception) {
            throw new GeneralException('Invalid date range');
        }
    }
}

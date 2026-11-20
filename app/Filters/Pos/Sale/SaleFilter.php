<?php

namespace App\Filters\Pos\Sale;

use App\Exceptions\GeneralException;
use App\Filters\Pos\Shop\ShopFilter;
use Illuminate\Support\Carbon;

class SaleFilter extends ShopFilter
{
    public function search($search = null): void
    {
        $this->builder->when($search, function ($query, $search) {
            $table = $query->getModel()->getTable();
            $query->where(function ($q) use ($search, $table) {
                $q->where($table.'.full_invoice_number', 'like', "%{$search}%")
                    ->orWhere($table.'.invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('items', function ($itemQuery) use ($search) {
                        $itemQuery->where('serial_number', 'like', "%{$search}%");
                    });
            });
        });

    }

    public function status($status = null): void
    {
        if (! $status) {
            return;
        }

        $status = explode(',', $status);

        $this->builder->when($status, function ($query, $status) {
            $query->where('status', $status);
        });
    }

    public function customer($customerId = null): void
    {
        $customerId = explode(',', $customerId);

        $this->builder->when($customerId, function ($query, $customerId) {
            $query->where($query->getModel()->getTable().'.contact_id', $customerId);
        });
    }

    public function seller($sellerId = null): void
    {
        $sellerId = explode(',', $sellerId);

        $this->builder->when($sellerId, function ($query, $sellerId) {
            $query->where($query->getModel()->getTable().'.seller_id', $sellerId);
        });
    }

    public function warehouse($warehouseId = null): void
    {
        $warehouseId = explode(',', $warehouseId);

        $this->builder->when($warehouseId, function ($query, $warehouseId) {
            $query->where('tenant_id', $warehouseId);
        });
    }

    public function brand($ids = null): void
    {
        if (! $ids) {
            return;
        }

        $brandIds = is_array($ids) ? $ids : explode(',', $ids);

        $this->builder->whereHas('items.product', function ($query) use ($brandIds) {
            $query->whereIn('brand_id', $brandIds);
        });
    }

    public function paymentMethod($ids = null): void
    {
        if (! $ids) {
            return;
        }

        $paymentMethodIds = is_array($ids) ? $ids : explode(',', $ids);

        $this->builder->whereHas('payments', function ($paymentQuery) use ($paymentMethodIds) {
            $paymentQuery->whereIn('payment_method_id', $paymentMethodIds);
        });
    }

    public function createdBy($ids = null): void
    {
        if (! $ids) {
            return;
        }

        $createdByIds = is_array($ids) ? $ids : explode(',', $ids);

        $this->builder->whereIn($this->builder->getModel()->getTable().'.created_by', $createdByIds);
    }

    public function date($range = null): void
    {
        try {
            $table = $this->builder->getModel()->getTable();
            // If no date is provided, default to today's date
            if (empty($range)) {
                $this->builder->whereDate($table.'.date', Carbon::today());

                return;
            }

            // Handle JSON string input from query params
            if (is_string($range)) {
                $range = json_decode($range, true);
            }

            if (! is_array($range) || count($range) < 2) {
                // Fallback to today if invalid structure
                $this->builder->whereDate($table.'.date', Carbon::today());

                return;
            }

            [$startDate, $endDate] = $range;

            $start = Carbon::parse($startDate)->startOfDay();
            $end = Carbon::parse($endDate)->endOfDay();

            $this->builder->whereBetween($table.'.date', [$start, $end]);
        } catch (\Exception) {
            throw new GeneralException('Invalid date range format.');
        }
    }
}

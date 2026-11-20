<?php

namespace App\Filters\Pos\Purchase;

use App\Filters\Pos\Shop\ShopFilter;
use Illuminate\Support\Carbon;
use App\Exceptions\GeneralException;

class PurchaseFilter extends ShopFilter
{
    public function search($search = null): void
    {
        $this->builder->when($search, function ($query, $search) {
            $query->where(function ($q) use ($search) {
                $q->where('full_invoice_number', 'like', "%{$search}%")
                    ->orWhere('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('items', function ($itemQuery) use ($search) {
                        $itemQuery->where('serial_number', 'like', "%{$search}%");
                    });
            });
        });
    }

    public function supplier($supplierId = null): void
    {
        $supplierId = explode(',', $supplierId);

        $this->builder->when($supplierId, function ($query, $supplierId) {
            $query->where('contact_id', $supplierId);
        });
    }

    public function status($status = null): void
    {
        if (!$status) {
            return;
        }

        $status = explode(',', $status);

        $this->builder->when($status, function ($query, $status) {
            $query->whereIn('status', $status);
        });
    }

    public function brand($ids = null): void
    {
        if (!$ids) {
            return;
        }

        $brandIds = is_array($ids) ? $ids : explode(',', $ids);

        $this->builder->whereHas('items.product', function ($query) use ($brandIds) {
            $query->whereIn('brand_id', $brandIds);
        });
    }

    public function date($range = null): void
    {
        try {
            // If no date is provided, default to today's date
            if (empty($range)) {
                $this->builder->whereDate('date', Carbon::today());
                return;
            }

            // Handle JSON string input from query params
            if (is_string($range)) {
                $range = json_decode($range, true);
            }

            if (!is_array($range) || count($range) < 2) {
                // Fallback to today if invalid structure
                $this->builder->whereDate('date', Carbon::today());
                return;
            }

            [$startDate, $endDate] = $range;

            $start = Carbon::parse($startDate)->startOfDay();
            $end = Carbon::parse($endDate)->endOfDay();

            $this->builder->whereBetween('date', [$start, $end]);
        } catch (\Exception $e) {
            throw new GeneralException('Invalid date range format.');
        }
    }
}

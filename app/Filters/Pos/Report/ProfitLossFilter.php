<?php

namespace App\Filters\Pos\Report;

use App\Exceptions\GeneralException;
use App\Filters\BaseFilter;
use Illuminate\Support\Carbon;

class ProfitLossFilter extends BaseFilter
{
    public function warehouse($warehouseId = null): void
    {
        $warehouseId = explode(',', $warehouseId);

        $this->builder->when($warehouseId, function ($query, $warehouseId) {
            $query->where('tenant_id', $warehouseId);
        });
    }

    public function product($productId = null): void
    {
        $productId = explode(',', $productId);

        $this->builder->when($productId, function ($query, $productId) {
            $query->whereIn('product_id', $productId);
        });
    }

    public function seller($sellerId = null): void
    {
        $sellerId = explode(',', $sellerId);

        $this->builder->when($sellerId, function ($query, $sellerId) {
            $query->whereHas('sale', function ($query) use ($sellerId) {
                $query->whereIn('seller_id', $sellerId);
            });
        });
    }

    public function category($categoryId = null): void
    {
        $categoryId = explode(',', $categoryId);

        $this->builder->when($categoryId, function ($query, $categoryId) {
            $query->whereHas('product', function ($query) use ($categoryId) {
                $query->whereIn('category_id', $categoryId);
            });
        });
    }

    public function brand($brandId = null): void
    {
        $brandId = explode(',', $brandId);

        $this->builder->when($brandId, function ($query, $brandId) {
            $query->whereHas('product', function ($query) use ($brandId) {
                $query->whereIn('brand_id', $brandId);
            });
        });
    }

    public function paymentMethod($paymentMethodId = null): void
    {
        $paymentMethodId = explode(',', $paymentMethodId);

        $this->builder->when($paymentMethodId, function ($query, $paymentMethodId) {
            $query->whereHas('sale.payments', function ($query) use ($paymentMethodId) {
                $query->whereIn('payment_method_id', $paymentMethodId);
            });
        });
    }

    public function status($status = null): void
    {
        $this->builder->when($status, function ($query, $status) {
            $query->whereHas('sale', function ($query) use ($status) {
                $query->where('status', $status);
            });
        });
    }

    public function createdBy($createdById = null): void
    {
        $createdById = explode(',', $createdById);

        $this->builder->when($createdById, function ($query, $createdById) {
            $query->whereHas('sale', function ($query) use ($createdById) {
                $query->whereIn('created_by', $createdById);
            });
        });
    }

    public function date($range = null): void
    {
        try {
            // If no date is provided, default to today's date
            if (empty($range)) {
                $this->builder->whereDate('sale_date', Carbon::today());

                return;
            }

            // Handle JSON string input from query params
            if (is_string($range)) {
                $range = json_decode($range, true);
            }

            if (! is_array($range) || count($range) < 2) {
                // Fallback to today if invalid structure
                $this->builder->whereDate('sale_date', Carbon::today());

                return;
            }

            [$startDate, $endDate] = $range;

            $start = Carbon::parse($startDate)->startOfDay();
            $end = Carbon::parse($endDate)->endOfDay();

            $this->builder->whereBetween('sale_date', [$start, $end]);
        } catch (\Exception) {
            throw new GeneralException('Invalid date range format.');
        }
    }
}

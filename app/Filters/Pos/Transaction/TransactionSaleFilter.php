<?php

namespace App\Filters\Pos\Transaction;

use App\Exceptions\GeneralException;
use App\Filters\Pos\Shop\ShopFilter;
use Carbon\Carbon;

class TransactionSaleFilter extends ShopFilter
{

    public function search($search = null): void
    {
        $this->builder->when($search, function ($query) use ($search) {
            $query->where('invoice_number', 'LIKE', "%{$search}%")
                ->orWhere('full_invoice_number', 'LIKE', "%{$search}%");
        });
    }

    public function customer($customerId = null): void
    {
        $customerId = explode(',', $customerId);

        $this->builder->when($customerId, function ($query) use ($customerId) {
            $query->whereHasMorph(
                'model',
                \App\Models\Pos\Sale\Sale::class,
                function ($query) use ($customerId) {
                    $query->whereIn('contact_id', $customerId);
                }
            );
        });
    }

    public function supplier($customerId = null): void
    {
        $customerId = explode(',', $customerId);

        $this->builder->when($customerId, function ($query) use ($customerId) {
            $query->whereHasMorph(
                'model',
                \App\Models\Pos\Purchase\Purchase::class,
                function ($query) use ($customerId) {
                    $query->whereIn('contact_id', $customerId);
                }
            );
        });
    }

    public function paymentMethod($paymentMethodId = null): void
    {
        $paymentMethodId = explode(',', $paymentMethodId);

        $this->builder->when($paymentMethodId, function ($query) use ($paymentMethodId) {
            $query->whereIn('payment_method_id', $paymentMethodId);
        });
    }

    public function status($status = null): void
    {
        if (!$status) {
            return;
        }

        $status = explode(',', $status);

        $this->builder->when($status, function ($query) use ($status) {
            $query->whereHasMorph('model', [
                \App\Models\Pos\Sale\Sale::class,
                \App\Models\Pos\Purchase\Purchase::class,
                \App\Models\Pos\Expense\Expense::class,
            ], function ($query) use ($status) {
                $query->whereIn('status', $status);
            });
        });
    }

    /**
     * @throws GeneralException
     */
    public function date($range = null): void
    {
        if (empty($range)) {
            return;
        }

        // Handle JSON string input from query params
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
        } catch (\Exception $e) {
            throw new GeneralException('Invalid your date range');
        }

        $this->builder->whereBetween('date', [$start, $end]);
    }
}

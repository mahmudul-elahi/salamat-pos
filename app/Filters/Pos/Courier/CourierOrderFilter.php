<?php

namespace App\Filters\Pos\Courier;

use App\Filters\BaseFilter;

class CourierOrderFilter extends BaseFilter
{
    public function search($invoiceNumber = null): void
    {
        $this->builder->when($invoiceNumber, function ($query, $invoiceNumber) {
            $query->where(function ($q) use ($invoiceNumber) {
                $q->where('merchant_order_id', 'like', "%{$invoiceNumber}%")
                    ->orWhere('consignment_id', 'like', "%{$invoiceNumber}%")
                    ->orWhere('tracking_code', 'like', "%{$invoiceNumber}%")
                    ->orWhere('recipient_phone', 'like', "%{$invoiceNumber}%")
                    ->orWhere('recipient_name', 'like', "%{$invoiceNumber}%")
                    ->orWhereHas('sale', function ($saleQuery) use ($invoiceNumber) {
                        $saleQuery->where('full_invoice_number', 'like', "%{$invoiceNumber}%");
                    });
            });
        });
    }

    public function provider($provider = null): void
    {
        if (empty($provider)) {
            return;
        }

        $provider = explode(',', $provider);

        $this->builder->when($provider, fn ($query) => $query->whereIn('provider', $provider));
    }
}

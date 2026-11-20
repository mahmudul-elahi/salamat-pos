<?php

namespace App\Filters\Ecommerce\Order;

use App\Filters\BaseFilter;
use Illuminate\Database\Eloquent\Builder;

class OrderFilter extends BaseFilter
{
    public function search($search = null)
    {
        $this->builder->when($search, function ($query) use ($search) {
            // Grouped in one closure — without it these ORs would escape any
            // constraint the caller already applied (e.g. ->where('contact_id', ...)
            // in ContactHistoryController), quietly matching orders never asked for.
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'LIKE', "%{$search}%")
                    ->orWhere('address', 'LIKE', "%{$search}%")
                    ->orWhereHas('customer', function ($q2) use ($search) {
                        $q2->where('name', 'LIKE', "%{$search}%")
                            ->orWhere('phone', 'LIKE', "%{$search}%")
                            ->orWhere('email', 'LIKE', "%{$search}%")
                            ->orWhere('address', 'LIKE', "%{$search}%");
                    });
            });
        });
    }

    public function warehouse($ids = null): void
    {
        $warehouseIds = is_array($ids) ? $ids : explode(',', (string) $ids);

        $this->builder->when($ids, function (Builder $query) use ($warehouseIds) {
            $query->whereIn($query->getModel()->getTable() . '.tenant_id', $warehouseIds);
        });
    }
}
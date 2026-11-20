<?php

namespace App\Filters\Pos\Report;

use App\Filters\BaseFilter;
use App\Filters\Common\Traits\SupplierShopFilterTrait;
use Illuminate\Database\Eloquent\Builder;

class SupplierReportFilter extends BaseFilter
{
    use SupplierShopFilterTrait;

    public function search($search = null): void
    {
        $this->builder->when($search, function (Builder $builder) use ($search) {
            $builder->where('name', 'LIKE', "%{$search}%")
                ->orWhere('email', 'LIKE', "%{$search}%");
        });
    }

    public function supplier($supplierIds = null): void
    {
        if (! $supplierIds) {
            return;
        }

        $ids = explode(',', $supplierIds);

        $this->builder->whereIn('contacts.id', $ids);
    }
}

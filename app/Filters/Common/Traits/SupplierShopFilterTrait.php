<?php

namespace App\Filters\Common\Traits;

trait SupplierShopFilterTrait
{
    public function shop($shopId = null): void
    {
        $shopId = explode(',', $shopId);

        $this->builder->when($shopId, function ($query, $shopId) {
            $query->whereHas('tenants', fn ($query) => $query->whereIn('tenant_id', $shopId));
        });
    }
}

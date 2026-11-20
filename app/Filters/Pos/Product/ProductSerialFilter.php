<?php

namespace App\Filters\Pos\Product;

use App\Filters\BaseFilter;
use App\Scopes\TenantScope;

class ProductSerialFilter extends BaseFilter
{
    public function search($search = null): void
    {
        $this->builder->when($search, function ($query) use ($search) {
            $query->where('serial_number', 'LIKE', '%' . $search . '%')
                ->orWhereRaw('RIGHT(serial_number, 4) = ?', [$search])
                ->orWhereHas('product', function ($query) use ($search) {
                    $query->where('product_name', 'LIKE', '%' . $search . '%');
                });
        });
    }

    public function status($status = null): void
    {
        $this->builder->when($status, function ($query) use ($status) {
            $query->where('status', $status);
        });
    }

    public function product($productId = null): void
    {
        $productId = explode(',', $productId);

        $this->builder->when($productId, function ($query, $productId) {
            $query->where('product_id', $productId);
        });
    }

    public function warehouse($warehouseId = null): void
    {
        if (empty($warehouseId)) {
            return;
        }

        $warehouseIds = is_array($warehouseId) ? $warehouseId : explode(',', $warehouseId);

        $this->builder->whereHas('tenantProductSerials', function ($q) use ($warehouseIds) {
            $q->whereIn('tenant_id', $warehouseIds)
                ->withoutGlobalScope(TenantScope::class);
        });
    }


    public function supplier($supplierId = null): void
    {
        $supplierIds = array_filter(explode(',', (string) $supplierId));

        $this->builder->when($supplierIds, function ($query, $supplierIds) {
            $query->whereHas('variant.purchaseItems.purchase', function ($q) use ($supplierIds) {
                $q->whereIn('contact_id', $supplierIds);
            });
        });
    }
}

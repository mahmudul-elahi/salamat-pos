<?php

namespace App\Models\Pos\Stock;

use App\Models\Landlord\Brand\Brand;
use App\Models\Pos\Product\Product;
use App\Models\Pos\Product\ProductVariant;
use App\Models\TenantBaseModel;

class Stock extends TenantBaseModel
{
    protected $fillable = [
        'tenant_id',
        'product_id',
        'product_variant_id',
        'quantity',
    ];

    public function product(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function productVariant(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function saleItems()
    {
        return $this->hasMany(
            \App\Models\Pos\Sale\SaleItem::class,
            'product_variant_id',
            'product_variant_id'
        );
    }
}

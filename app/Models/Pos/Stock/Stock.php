<?php

namespace App\Models\Pos\Stock;

use App\Models\Pos\Product\Product;
use App\Models\Pos\Product\ProductVariant;
use App\Models\Pos\Sale\SaleItem;
use App\Models\TenantBaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Stock extends TenantBaseModel
{
    #[\Override]
    protected $fillable = [
        'tenant_id',
        'product_id',
        'product_variant_id',
        'quantity',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function saleItems()
    {
        return $this->hasMany(
            SaleItem::class,
            'product_variant_id',
            'product_variant_id'
        );
    }
}

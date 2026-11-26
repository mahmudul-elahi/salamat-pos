<?php

namespace App\Models\Pos\Sale;

use App\Models\Common\BaseModel;
use App\Models\Pos\Product\Product;
use App\Models\Pos\Product\ProductCare;
use App\Models\Pos\Product\ProductVariant;
use App\Models\Pos\SaleReturn\SaleReturnItem;
use App\Models\PreOwned\PreOwnedCare;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SaleItem extends BaseModel
{
    use SoftDeletes;

    protected $dates = ['deleted_at'];

    #[\Override]
    protected $fillable = [
        'sale_id',
        'product_id',
        'product_variant_id',
        'quantity',
        'unit_price',
        'discount_type',
        'discount_amount',
        'serial_number',
        'sale_type',
        'product_care_id',
        'pre_owned_care_id',
        'care_amount',
        'note',
        'updated_at',
        'created_at',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function productCare(): BelongsTo
    {
        return $this->belongsTo(ProductCare::class, 'product_care_id');
    }

    public function preOwnedCare(): BelongsTo
    {
        return $this->belongsTo(PreOwnedCare::class, 'pre_owned_care_id');
    }

    public function getCareNameAttribute(): ?string
    {
        return $this->productCare?->care?->name ?? $this->preOwnedCare?->care?->name;
    }

    public function saleReturnItems()
    {
        return $this->hasMany(
            SaleReturnItem::class,
            'sale_id',
            'sale_id'
        )
            ->whereColumn('product_id', 'sale_items.product_id')
            ->whereColumn('product_variant_id', 'sale_items.product_variant_id')
            ->whereColumn('serial_number', 'sale_items.serial_number');
    }
}

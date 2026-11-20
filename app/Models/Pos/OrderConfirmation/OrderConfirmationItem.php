<?php

namespace App\Models\Pos\OrderConfirmation;

use App\Models\Common\BaseModel;
use App\Models\Pos\Product\Product;
use App\Models\Pos\Product\ProductVariant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderConfirmationItem extends BaseModel
{
    protected $fillable = [
        'order_confirmation_id',
        'product_id',
        'product_variant_id',
        'custom_product',
        'quantity',
        'unit_price',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
    ];

    protected $appends = ['product_name', 'variant_attributes', 'line_total'];

    public function getProductNameAttribute(): ?string
    {
        return $this->product?->product_name ?? $this->custom_product;
    }

    public function getVariantAttributesAttribute(): ?string
    {
        return $this->variant?->attributeValues
            ->sortBy(fn ($attrValue) => $attrValue->attribute->name)
            ->map(fn ($attrValue) => $attrValue->value)
            ->join(' / ') ?: null;
    }

    public function getLineTotalAttribute(): float
    {
        return round((float) $this->quantity * (float) $this->unit_price, 2);
    }

    public function orderConfirmation(): BelongsTo
    {
        return $this->belongsTo(OrderConfirmation::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}

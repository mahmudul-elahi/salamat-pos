<?php

namespace App\Models\Pos\Product;

use App\Models\Common\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductVariant extends BaseModel
{
    use SoftDeletes;

    protected $fillable = [
        'product_id',
        'sku',
        'stock',
        'is_prebook',
        'status'
    ];

    public $casts = [
        'stock' => 'integer',
        'is_prebook' => 'boolean'
    ];

    public function product(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function attributeValues(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(AttributeValue::class, 'variant_attribute_values')->withTimestamps();
    }

    public function price(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(VariantPrice::class, 'product_variant_id', 'id');
    }

    public function serials()
    {
        return $this->hasMany(ProductSerial::class, 'product_variant_id', 'id');
    }

    public function purchaseItems()
    {
        return $this->hasMany(\App\Models\Pos\Purchase\PurchaseItem::class, 'product_variant_id', 'id');
    }

    public function saleItems()
    {
        return $this->hasMany(\App\Models\Pos\Sale\SaleItem::class, 'product_variant_id', 'id');
    }
}

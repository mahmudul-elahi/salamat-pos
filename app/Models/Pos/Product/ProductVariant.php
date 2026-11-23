<?php

namespace App\Models\Pos\Product;

use App\Models\Common\BaseModel;
use App\Models\Pos\Purchase\PurchaseItem;
use App\Models\Pos\Sale\SaleItem;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductVariant extends BaseModel
{
    use SoftDeletes;

    #[\Override]
    protected $fillable = [
        'product_id',
        'sku',
        'stock',
        'is_prebook',
        'status',
    ];

    #[\Override]
    public $casts = [
        'stock' => 'integer',
        'is_prebook' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function attributeValues(): BelongsToMany
    {
        return $this->belongsToMany(AttributeValue::class, 'variant_attribute_values')->withTimestamps();
    }

    public function price(): HasOne
    {
        return $this->hasOne(VariantPrice::class, 'product_variant_id', 'id');
    }

    public function serials()
    {
        return $this->hasMany(ProductSerial::class, 'product_variant_id', 'id');
    }

    public function purchaseItems()
    {
        return $this->hasMany(PurchaseItem::class, 'product_variant_id', 'id');
    }

    public function saleItems()
    {
        return $this->hasMany(SaleItem::class, 'product_variant_id', 'id');
    }
}

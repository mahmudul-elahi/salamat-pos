<?php

namespace App\Models\Pos\Purchase;

use App\Models\Pos\Product\Product;
use App\Models\Pos\Product\ProductVariant;
use App\Models\Pos\PurchaseReturn\PurchaseReturnItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseItem extends Model
{
    use SoftDeletes;

    protected $dates = ['deleted_at'];

    protected $fillable = [
        'purchase_id',
        'product_id',
        'product_variant_id',
        'quantity',
        'unit_price',
        'serial_number',
        'note',
        'purchase_type',
    ];

    public $casts = [
        'purchase_id' => 'int',
        'product_id' => 'int',
        'product_variant_id' => 'int',
        'quantity' => 'int',
        'unit_price' => 'float',
    ];

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function purchaseReturnItems()
    {
        return $this->hasMany(
            PurchaseReturnItem::class,
            'purchase_id',
            'purchase_id'
        )
            ->whereColumn('product_id', 'purchase_items.product_id')
            ->whereColumn('product_variant_id', 'purchase_items.product_variant_id')
            ->whereColumn('serial_number', 'purchase_items.serial_number');
    }
}

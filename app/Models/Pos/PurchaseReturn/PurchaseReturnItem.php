<?php

namespace App\Models\Pos\PurchaseReturn;

use App\Models\Pos\Product\Product;
use Illuminate\Database\Eloquent\Model;
use App\Models\Pos\Product\ProductVariant;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseReturnItem extends Model
{
    use SoftDeletes;

    protected $dates = ['deleted_at'];

    protected $fillable = [
        'purchase_return_id',
        'product_id',
        'product_variant_id',
        'purchase_id',
        'quantity',
        'unit_price',
        'serial_number',
        'note',
    ];

    public function purchaseReturn(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(PurchaseReturn::class);
    }

    public function product(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productVariant(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }
}

<?php

namespace App\Models\Ecommerce\Order;

use App\Models\Pos\Product\Product;
use App\Models\Pos\Product\ProductCare;
use App\Models\Pos\Product\ProductVariant;
use App\Models\PreOwned\PreOwned;
use App\Models\PreOwned\PreOwnedCare;
use App\Models\Product\GiftProductVariant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderList extends Model
{
    // The table has always had a deleted_at column but no trait to use it.
    // Removing a line from a placed order is the first thing that deletes one,
    // and that history is worth keeping — a customer disputing what they were
    // charged for shouldn't run into a row that simply vanished. Safe to add
    // now: no row has ever been soft-deleted, so nothing disappears from any
    // existing query.
    use SoftDeletes;

    protected $fillable = [
        'order_id',
        'product_id',
        'pre_owned_id',
        'product_price',
        'final_price',
        'discount_type',
        'discount_value',
        'discount_amount',
        'discount_total',
        'quantity',
        'purchase_point',
        'status',
        'product_variant_id',
        'pre_owned_id',
        'gift_product_id',
        'gift_product_variant_id',
        'product_care_id',
        'pre_owned_care_id',
    ];

    public function order(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function preOwned(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(PreOwned::class, 'pre_owned_id');
    }

    public function giftProductVariant()
    {
        return $this->belongsTo(GiftProductVariant::class, 'gift_product_variant_id', 'id');
    }

    public function productCare()
    {
        return $this->belongsTo(ProductCare::class, 'product_care_id', 'id');
    }

    public function preOwnedCare()
    {
        return $this->belongsTo(PreOwnedCare::class, 'pre_owned_care_id', 'id');
    }
}

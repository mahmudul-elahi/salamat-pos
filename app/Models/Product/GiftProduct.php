<?php

namespace App\Models\Product;

use App\Helpers\Traits\RevalidatesFrontendTrait;
use App\Models\Ecommerce\Order\OrderList;
use App\Models\Pos\Product\Product;
use App\Models\PreOwned\PreOwned;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class GiftProduct extends Model
{
    use RevalidatesFrontendTrait, SoftDeletes;

    protected $revalidationType = 'gift-product';

    protected $fillable = [
        'product_id',
        'pre_owned_id',
        'is_active',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function preOwned(): BelongsTo
    {
        return $this->belongsTo(PreOwned::class, 'pre_owned_id');
    }

    public function giftVariants(): HasMany
    {
        return $this->hasMany(GiftProductVariant::class, 'gift_product_id');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderList::class, 'gift_product_id', 'id');
    }
}

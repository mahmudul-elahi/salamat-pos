<?php

declare(strict_types=1);

namespace App\Models\Ecommerce\Offer;

use App\Models\Pos\Product\Product;
use App\Models\Pos\Product\ProductVariant;
use Illuminate\Database\Eloquent\Model;

class OfferProduct extends Model
{
    protected $fillable = [
        'offer_id',
        'product_id',
        'product_variant_id',
        'discount_type',
        'discount_amount',
        'special_price',
    ];

    public function offer()
    {
        return $this->belongsTo(Offer::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}

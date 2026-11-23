<?php

declare(strict_types=1);

namespace App\Models\PreOwned;

use App\Helpers\Traits\RevalidatesFrontendTrait;
use App\Models\Common\BaseModel;
use App\Models\Pos\Product\Product;
use App\Models\Pos\Product\ProductVariant;
use Illuminate\Database\Eloquent\SoftDeletes;

class PreOwned extends BaseModel
{
    use RevalidatesFrontendTrait, SoftDeletes;

    protected $revalidationType = 'used-device';

    protected string $revalidationSlugField = 'product.slug';

    #[\Override]
    protected $fillable = [
        'product_id',
        'product_variant_id',
        'price',
        'booking_amount',
        'purchase_point',
        'is_sold',
        'serial_number',
        'battery_health',
        'discount_type',
        'discount_amount',
        'box',
        'charging_time',
        'official_service_warranty',
        'thumbnail_image',
        'description',
    ];

    #[\Override]
    public $casts = [
        'is_sold' => 'boolean',
        'box' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id', 'id');
    }

    public function attribute()
    {
        return $this->hasMany(PreOwnedAttribute::class, 'pre_owned_id', 'id');
    }

    public function images()
    {
        return $this->hasMany(PreOwnedImage::class, 'pre_owned_id', 'id');
    }

    public function cares()
    {
        return $this->hasMany(PreOwnedCare::class, 'pre_owned_id', 'id');
    }
}

<?php

namespace App\Models\Coupon;

use App\Models\Landlord\Brand\Brand;
use App\Models\Landlord\Category\Category;
use App\Models\PreOwned\PreOwned;
use App\Models\Pos\Product\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CouponProductScope extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'new_coupon_id',
        'scope_type',  // all, category, product, brand, pre_owned
        'category_id',
        'product_id',
        'brand_id',
        'pre_owned_id',
    ];

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(NewCoupon::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function preOwned(): BelongsTo
    {
        return $this->belongsTo(PreOwned::class);
    }
}

<?php

namespace App\Models\Coupon;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CouponCustomerScope extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'new_coupon_id',
        'customer_level_id',
        'point_amount',
        'discount_amount',
    ];

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(NewCoupon::class);
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(CustomerLevel::class, 'customer_level_id');
    }
}

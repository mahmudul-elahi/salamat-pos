<?php

namespace App\Models\Coupon;

use App\Models\Customer\Customer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerCouponLog extends Model
{
    protected $fillable = [
        'new_coupon_id',
        'customer_id',
        'used_at',
    ];

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(NewCoupon::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}

<?php

namespace App\Models\Coupon;

use App\Helpers\Traits\RevalidatesFrontendTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class NewCoupon extends Model
{
    use SoftDeletes, RevalidatesFrontendTrait;

    protected $revalidationType = 'coupon';

    protected $fillable = [
        'code',
        'type',
        'discount_type',
        'discount_value',
        'start_date',
        'end_date',
        'usage_limit',
        'min_purchase_amount',
        'status',
        'is_public',
    ];

    public $casts = [
        'is_public' => 'boolean',
    ];

    public function productScopes(): HasMany
    {
        return $this->hasMany(CouponProductScope::class);
    }

    public function customerScopes(): HasMany
    {
        return $this->hasMany(CouponCustomerScope::class);
    }

    public function usages(): HasMany
    {
        return $this->hasMany(CustomerCouponLog::class);
    }
}

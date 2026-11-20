<?php

namespace App\Models\Coupon;

use App\Models\Common\BaseModel;
use App\Models\Customer\Customer;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerLevel extends BaseModel
{
    use SoftDeletes;

    protected $fillable = [
        'name',         // bronze, silver, gold, platinum
        'min_points',
        'max_points'
    ];

    public function couponScopes(): HasMany
    {
        return $this->hasMany(CouponCustomerScope::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }
}

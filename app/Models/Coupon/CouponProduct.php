<?php

declare(strict_types=1);

namespace App\Models\Coupon;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CouponProduct extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'product_id',
        'coupon_id',
    ];
}

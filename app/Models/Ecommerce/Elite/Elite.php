<?php

declare(strict_types=1);

namespace App\Models\Ecommerce\Elite;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Elite extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'status_name',
        'tagline',
        'logo',
        'banner',
        'benefits',
        'point',
    ];

    protected $casts = [
        'benefits' => 'array',
    ];
}

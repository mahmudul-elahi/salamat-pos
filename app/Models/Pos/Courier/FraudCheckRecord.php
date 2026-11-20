<?php

declare(strict_types=1);

namespace App\Models\Pos\Courier;

use App\Models\Common\BaseModel;

class FraudCheckRecord extends BaseModel
{
    protected $fillable = [
        'phone',
        'provider',
        'success',
        'cancel',
        'total',
        'success_ratio',
        'source',
        'raw_response',
        'checked_at',
    ];

    protected $casts = [
        'raw_response' => 'array',
        'checked_at' => 'datetime',
    ];
}

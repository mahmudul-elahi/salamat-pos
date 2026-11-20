<?php

declare(strict_types=1);

namespace App\Models\Hrm;

use Illuminate\Database\Eloquent\Model;

class PayrunType extends Model
{
    protected $fillable = [
        'name',
        'payrun_period',
        'consider_type',
        'consider_overtime',
        'is_default',
    ];

    protected $casts = [
        'consider_overtime' => 'boolean',
        'is_default' => 'boolean',
    ];
}

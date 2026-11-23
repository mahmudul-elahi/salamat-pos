<?php

declare(strict_types=1);

namespace App\Models\Hrm;

use Illuminate\Database\Eloquent\Model;

class Beneficiary extends Model
{
    #[\Override]
    protected $fillable = [
        'name',
        'type',
        'is_active',
        'description',
    ];

    #[\Override]
    protected $casts = [
        'is_active' => 'boolean',
    ];
}

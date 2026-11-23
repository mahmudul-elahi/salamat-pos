<?php

declare(strict_types=1);

namespace App\Models\Pos\Care;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Care extends Model
{
    use SoftDeletes;

    #[\Override]
    protected $fillable = [
        'name',
        'value',
    ];
}

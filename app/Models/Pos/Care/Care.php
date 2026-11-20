<?php

namespace App\Models\Pos\Care;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Care extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'value',
    ];
}

<?php

namespace App\Models\Customization;

use Illuminate\Database\Eloquent\Model;

class Customization extends Model
{
    protected $fillable = ['name', 'value', 'type'];
}

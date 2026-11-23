<?php

declare(strict_types=1);

namespace App\Models\Hrm;

use Illuminate\Database\Eloquent\Model;

class Designation extends Model
{
    #[\Override]
    protected $fillable = ['name', 'description'];
}

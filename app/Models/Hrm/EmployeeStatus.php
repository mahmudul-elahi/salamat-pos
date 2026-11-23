<?php

declare(strict_types=1);

namespace App\Models\Hrm;

use Illuminate\Database\Eloquent\Model;

class EmployeeStatus extends Model
{
    #[\Override]
    protected $fillable = ['name', 'color', 'description'];
}

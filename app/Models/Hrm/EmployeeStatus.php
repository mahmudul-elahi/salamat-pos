<?php

namespace App\Models\Hrm;

use Illuminate\Database\Eloquent\Model;

class EmployeeStatus extends Model
{
    protected $fillable = ['name', 'color', 'description'];
}

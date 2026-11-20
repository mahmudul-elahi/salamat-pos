<?php

declare(strict_types=1);

namespace App\Models\Hrm;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    protected $fillable = ['name', 'department_head_id'];

    public function departmentHead()
    {
        return $this->belongsTo(User::class, 'department_head_id');
    }

    public function workShifts()
    {
        return $this->belongsToMany(WorkShift::class, 'work_shift_department');
    }
}

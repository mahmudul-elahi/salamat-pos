<?php

namespace App\Models\Hrm;

use Illuminate\Database\Eloquent\Model;

class WorkShiftSchedule extends Model
{
    protected $fillable = ['work_shift_id', 'day_name', 'start_time', 'end_time'];
}

<?php

declare(strict_types=1);

namespace App\Models\Hrm;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class WorkShift extends Model
{
    protected $fillable = [
        'shift_name', 'working_shift_type', 'start_time', 'end_time', 'weekend_days',
    ];

    protected $casts = [
        'weekend_days' => 'array',
    ];

    public function breakTimes()
    {
        return $this->belongsToMany(BreakTime::class, 'work_shift_break_time');
    }

    public function departments()
    {
        return $this->belongsToMany(Department::class, 'work_shift_department');
    }

    public function schedules()
    {
        return $this->hasMany(WorkShiftSchedule::class);
    }

    public function employees()
    {
        return $this->belongsToMany(User::class, 'work_shift_user');
    }
}

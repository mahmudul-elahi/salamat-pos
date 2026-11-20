<?php

namespace App\Models\Hrm;

use Illuminate\Database\Eloquent\Model;

class LeaveType extends Model
{
    protected $fillable = ['name', 'type', 'day', 'enable'];

    protected $casts = ['enable' => 'boolean'];

    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class);
    }
}

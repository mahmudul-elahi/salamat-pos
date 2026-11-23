<?php

declare(strict_types=1);

namespace App\Models\Hrm;

use Illuminate\Database\Eloquent\Model;

class LeaveType extends Model
{
    #[\Override]
    protected $fillable = ['name', 'type', 'day', 'enable'];

    #[\Override]
    protected $casts = ['enable' => 'boolean'];

    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class);
    }
}

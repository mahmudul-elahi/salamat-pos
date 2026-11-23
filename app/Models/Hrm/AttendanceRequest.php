<?php

namespace App\Models\Hrm;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceRequest extends Model
{
    #[\Override]
    protected $fillable = [
        'user_id',
        'date',
        'in_time',
        'out_time',
        'note',
        'status',
        'reviewed_by',
        'attendance_detail_id',
    ];

    #[\Override]
    protected $casts = [
        'date' => 'date',
        'in_time' => 'datetime',
        'out_time' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function attendanceDetail(): BelongsTo
    {
        return $this->belongsTo(AttendanceDetail::class);
    }
}

<?php

namespace App\Models\Hrm;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceBreak extends Model
{
    #[\Override]
    protected $fillable = [
        'attendance_id',
        'break_time_id',
        'started_at',
        'ended_at',
    ];

    #[\Override]
    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    public function breakTime(): BelongsTo
    {
        return $this->belongsTo(BreakTime::class);
    }

    public function durationSeconds(): int
    {
        $end = $this->ended_at ?? now();

        return (int) abs($this->started_at->diffInSeconds($end));
    }

    public function durationMinutes(): int
    {
        return (int) ($this->durationSeconds() / 60);
    }
}

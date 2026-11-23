<?php

namespace App\Models\Hrm;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class AttendanceDetail extends Model
{
    #[\Override]
    protected $fillable = [
        'attendance_details_id',
        'in_time',
        'out_time',
        'attendance_id',
        'review_by',
        'added_by',
        'in_ip_data',
        'out_ip_data',
        'in_note',
        'out_note',
        'in_selfie',
        'out_selfie',
        'work_from_home',
    ];

    #[\Override]
    protected $casts = [
        'in_time' => 'datetime',
        'out_time' => 'datetime',
        'work_from_home' => 'boolean',
    ];

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    /**
     * The parent punch session this detail is linked to (e.g. a break session
     * linked back to the main punch-in record).
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(AttendanceDetail::class, 'attendance_details_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(AttendanceDetail::class, 'attendance_details_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'review_by');
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    public function getInSelfieUrlAttribute(): ?string
    {
        return $this->in_selfie ? Storage::url($this->in_selfie) : null;
    }

    public function getOutSelfieUrlAttribute(): ?string
    {
        return $this->out_selfie ? Storage::url($this->out_selfie) : null;
    }
}

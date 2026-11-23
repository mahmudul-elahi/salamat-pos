<?php

namespace App\Models\Hrm;

use App\Filters\BaseFilter;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attendance extends Model
{
    #[\Override]
    protected $fillable = [
        'in_date',
        'user_id',
        'status',
        'work_shift_id',
        'behavior',
    ];

    #[\Override]
    protected $casts = [
        'in_date' => 'date',
    ];

    public function scopeFilters(Builder $query, BaseFilter $filter): Builder
    {
        return $filter->apply($query);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function workShift(): BelongsTo
    {
        return $this->belongsTo(WorkShift::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(AttendanceDetail::class);
    }

    public function breaks(): HasMany
    {
        return $this->hasMany(AttendanceBreak::class);
    }
}

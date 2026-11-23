<?php

namespace App\Models\Hrm;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payslip extends Model
{
    public const STATUS_GENERATED = 'generated';

    public const STATUS_SENT = 'sent';

    #[\Override]
    protected $fillable = [
        'employee_id',
        'department_id',
        'payrun_id',
        'start_date',
        'end_date',
        'basic_salary',
        'net_salary',
        'period',
        'consider_overtime',
        'consider_type',
        'status',
        'sent_at',
    ];

    #[\Override]
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'basic_salary' => 'float',
        'net_salary' => 'float',
        'consider_overtime' => 'boolean',
        'sent_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function payrun(): BelongsTo
    {
        return $this->belongsTo(Payrun::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PayslipItem::class);
    }
}

<?php

namespace App\Models\Hrm;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payrun extends Model
{
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_GENERATED = 'generated';
    public const STATUS_FAILED = 'failed';
    public const STATUS_SENT = 'sent';

    public const TYPE_CUSTOMIZED = 'customized';
    public const TYPE_SETTINGS = 'settings';
    public const TYPE_AUTO = 'auto';
    public const TYPE_MANUAL = 'manual';

    protected $fillable = [
        'name',
        'batch_id',
        'uuid',
        'start_date',
        'end_date',
        'total_employees',
        'data',
        'status',
        'type',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'data' => 'array',
    ];

    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class);
    }
}

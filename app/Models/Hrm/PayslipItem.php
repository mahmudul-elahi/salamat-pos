<?php

namespace App\Models\Hrm;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayslipItem extends Model
{
    public const TYPE_BASIC = 'basic';

    public const TYPE_EARNING = 'earning';

    public const TYPE_DEDUCTION = 'deduction';

    #[\Override]
    protected $fillable = [
        'payslip_id',
        'beneficiary_id',
        'type',
        'label',
        'amount',
    ];

    #[\Override]
    protected $casts = [
        'amount' => 'float',
    ];

    public function payslip(): BelongsTo
    {
        return $this->belongsTo(Payslip::class);
    }

    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(Beneficiary::class);
    }
}

<?php

namespace App\Models\Ecommerce\Emi;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmiTenure extends Model
{
    #[\Override]
    protected $fillable = ['emi_bank_id', 'months', 'charge_rate', 'is_active'];

    #[\Override]
    protected $casts = ['is_active' => 'boolean', 'charge_rate' => 'float'];

    public function emiBank(): BelongsTo
    {
        return $this->belongsTo(EmiBank::class);
    }
}

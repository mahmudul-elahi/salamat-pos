<?php

namespace App\Models\Tenant\Balance;

use App\Models\Pos\Contact\Contact;
use App\Models\Tenant\Tenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdvanceCredit extends Model
{
    #[\Override]
    protected $fillable = [
        'tenant_id',
        'contact_id',
        'amount',
        'note',
        'date',
        'type',
    ];

    #[\Override]
    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }
}

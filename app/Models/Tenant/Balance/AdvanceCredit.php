<?php

namespace App\Models\Tenant\Balance;

use App\Models\Pos\Contact\Contact;
use App\Models\Tenant\Tenant;
use Illuminate\Database\Eloquent\Model;

class AdvanceCredit extends Model
{
    protected $fillable = [
        'tenant_id',
        'contact_id',
        'amount',
        'note',
        'date',
        'type'
    ];

    protected $casts = [
        'amount' => 'decimal:2'
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function contact(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }
}

<?php

namespace App\Models\Pos\PartyMapping;

use App\Models\Customer\Customer;
use App\Models\Pos\Supplier\Supplier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartyMapping extends Model
{
    protected $fillable = [
        'customer_id',
        'supplier_id',
        'tenant_id',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'id');
    }
}

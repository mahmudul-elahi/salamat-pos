<?php

namespace App\Models\Pos\Product;

use App\Models\TenantBaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantProductSerial extends TenantBaseModel
{
    #[\Override]
    protected $fillable = [
        'tenant_id',
        'product_serial_id',
        'status',
    ];

    public function productSerial(): BelongsTo
    {
        return $this->belongsTo(ProductSerial::class, 'product_serial_id', 'id');
    }
}

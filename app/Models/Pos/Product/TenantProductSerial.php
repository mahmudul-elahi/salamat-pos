<?php

namespace App\Models\Pos\Product;

use App\Models\Pos\Product\ProductSerial;
use App\Models\TenantBaseModel;

class TenantProductSerial extends TenantBaseModel
{
    protected $fillable = [
        'tenant_id',
        'product_serial_id',
        'status'
    ];

    public function productSerial(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ProductSerial::class, 'product_serial_id', 'id');
    }
}

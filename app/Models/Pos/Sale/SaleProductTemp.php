<?php

namespace App\Models\Pos\Sale;

use App\Models\Common\BaseModel;

class SaleProductTemp extends BaseModel
{
    protected $fillable = [
        'product_id',
        'product_variant_id',
        'serial_number',
        'tenant_id',
    ];
}

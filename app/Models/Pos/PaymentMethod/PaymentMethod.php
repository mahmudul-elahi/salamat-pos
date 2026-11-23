<?php

declare(strict_types=1);

namespace App\Models\Pos\PaymentMethod;

use App\Models\Common\BaseModel;

class PaymentMethod extends BaseModel
{
    #[\Override]
    protected $fillable = [
        'name',
        'tenant_id',
    ];
}

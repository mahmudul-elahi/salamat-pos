<?php

namespace App\Models\Pos\PaymentMethod;

use App\Models\Common\BaseModel;
use Illuminate\Database\Eloquent\Model;
use App\Models\Setting\Setting;

class PaymentMethod extends BaseModel
{
    protected $fillable = [
        'name',
        'tenant_id'
    ];
}

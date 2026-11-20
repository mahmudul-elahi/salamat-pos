<?php

namespace App\Models\Pos\Income;

use App\Models\Common\BaseModel;
use App\Models\Tenant\Tenant;
use App\Models\TenantBaseModel;
use App\Models\User;

class IncomeCategory extends TenantBaseModel
{
    protected $fillable = [
        'name', 'director_id','tenant_id'

    ];

    public function incomes()
    {
        return $this->hasMany(Income::class);
    }

    public function director()
    {
        return $this->belongsTo(User::class, 'director_id', 'id');
    }
}

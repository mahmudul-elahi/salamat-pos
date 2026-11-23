<?php

declare(strict_types=1);

namespace App\Models\Pos\Income;

use App\Models\TenantBaseModel;
use App\Models\User;

class IncomeCategory extends TenantBaseModel
{
    #[\Override]
    protected $fillable = [
        'name', 'director_id', 'tenant_id',

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

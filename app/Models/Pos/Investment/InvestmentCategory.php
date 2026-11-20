<?php

namespace App\Models\Pos\Investment;

use App\Models\TenantBaseModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvestmentCategory extends TenantBaseModel
{

    protected $fillable = [
        'name',
        'director_id',
        'tenant_id'
    ];

    public function director(): BelongsTo
    {
        return $this->belongsTo(User::class, 'director_id', 'id');
    }

    public function investments()
    {
        return $this->hasMany(Investment::class, 'investment_category_id', 'id');
    }
}

<?php

namespace App\Models\Ecommerce\Emi;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmiBank extends Model
{
    protected $fillable = ['bank_name', 'gw_key', 'logo', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function tenures(): HasMany
    {
        return $this->hasMany(EmiTenure::class)->orderBy('months');
    }
}

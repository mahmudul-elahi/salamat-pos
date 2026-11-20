<?php

namespace App\Models\Pos\Telesale;

use App\Models\Common\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TelesaleResult extends BaseModel
{
    protected $fillable = [
        'name',
        'color',
        'sort_order',
        'status',
    ];

    protected $casts = [
        'status'     => 'boolean',
        'sort_order' => 'integer',
    ];

    public function telesales(): HasMany
    {
        return $this->hasMany(Telesale::class);
    }
}

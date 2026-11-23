<?php

namespace App\Models\Hrm;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssetCategory extends Model
{
    #[\Override]
    protected $fillable = ['name', 'status'];

    #[\Override]
    protected $casts = ['status' => 'boolean'];

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }
}

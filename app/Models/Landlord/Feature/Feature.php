<?php

namespace App\Models\Landlord\Feature;

use App\Models\Landlord\Plan\Plan;
use Illuminate\Database\Eloquent\Model;

class Feature extends Model
{
    protected $fillable = [
        'name',
        'label',
        'group_name',
    ];

    public function plans(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Plan::class);
    }
}

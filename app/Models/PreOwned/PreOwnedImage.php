<?php

namespace App\Models\PreOwned;

use Illuminate\Database\Eloquent\Model;

class PreOwnedImage extends Model
{
    protected $fillable = [
        'pre_owned_id',
        'image',
        'is_primary',
    ];
}

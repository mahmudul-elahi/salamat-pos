<?php

declare(strict_types=1);

namespace App\Models\PreOwned;

use Illuminate\Database\Eloquent\Model;

class PreOwnedImage extends Model
{
    #[\Override]
    protected $fillable = [
        'pre_owned_id',
        'image',
        'is_primary',
    ];
}

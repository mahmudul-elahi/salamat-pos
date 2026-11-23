<?php

declare(strict_types=1);

namespace App\Models\PreOwned;

use Illuminate\Database\Eloquent\Model;

class PreOwnedProductSeo extends Model
{
    #[\Override]
    protected $fillable = [
        'product_id',
        'title',
        'description',
        'long_description',
        'tags',
    ];
}

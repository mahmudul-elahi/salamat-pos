<?php

namespace App\Models\PreOwned;

use Illuminate\Database\Eloquent\Model;

class PreOwnedProductSeo extends Model
{
    protected $fillable = [
        'product_id',
        'title',
        'description',
        'long_description',
        'tags',
    ];
}

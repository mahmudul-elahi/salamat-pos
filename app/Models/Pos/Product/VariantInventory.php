<?php

declare(strict_types=1);

namespace App\Models\Pos\Product;

use App\Models\Common\BaseModel;

class VariantInventory extends BaseModel
{
    #[\Override]
    protected $fillable = ['product_variant_id', 'stock'];

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class);
    }
}

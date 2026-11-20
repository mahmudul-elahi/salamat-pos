<?php

namespace App\Models\Pos\Product;

use App\Models\Common\BaseModel;
use App\Models\Pos\Product\ProductVariant;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VariantInventory extends BaseModel
{
    protected $fillable = ['product_variant_id', 'stock'];

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class);
    }
}

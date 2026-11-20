<?php

declare(strict_types=1);

namespace App\Models\Pos\DamageProduct;

use App\Models\Common\BaseModel;
use App\Models\Pos\Product\Product;
use App\Models\Pos\Product\ProductSerial;
use App\Models\Pos\Product\ProductVariant;

class DamageProductItem extends BaseModel
{
    protected $fillable = [
        'damage_product_id',
        'product_id',
        'product_variant_id',
        'product_serial_id',
        'quantity',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function productSerial()
    {
        return $this->belongsTo(ProductSerial::class, 'product_serial_id');
    }
}

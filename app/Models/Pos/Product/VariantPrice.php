<?php

declare(strict_types=1);

namespace App\Models\Pos\Product;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VariantPrice extends Model
{
    use SoftDeletes;

    #[\Override]
    protected $fillable = [
        'product_variant_id',
        'dealer_price',
        'retail_price',
        'b2b_price',
        'max_retail_price',
        'discount_percent',
        'discount_fixed',
    ];

    #[\Override]
    public $casts = [
        'dealer_price' => 'decimal:2',
    ];
}

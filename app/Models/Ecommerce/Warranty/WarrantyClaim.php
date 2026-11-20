<?php

namespace App\Models\Ecommerce\Warranty;

use App\Models\Common\BaseModel;
use App\Models\Pos\Product\Product;
use App\Models\Pos\Product\ProductVariant;
use App\Models\Pos\Sale\Sale;
use Illuminate\Database\Eloquent\SoftDeletes;

class WarrantyClaim extends BaseModel
{
    use SoftDeletes;

    protected $fillable = [
        'serial_number',
        'product_id',
        'product_variant_id',
        'sale_id',
        'reason',
        'warranty_end_date',
        'is_within_warranty',
        'status',
    ];

    protected $casts = [
        'warranty_end_date' => 'date',
        'is_within_warranty' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }
}

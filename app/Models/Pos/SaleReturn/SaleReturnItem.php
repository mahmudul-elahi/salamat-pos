<?php

namespace App\Models\Pos\SaleReturn;

use App\Models\Pos\Product\Product;
use App\Models\Pos\Sale\Sale;
use Illuminate\Database\Eloquent\Model;
use App\Models\Pos\Product\ProductVariant;
use Illuminate\Database\Eloquent\SoftDeletes;

class SaleReturnItem extends Model
{
    use SoftDeletes;

    protected $dates = ['deleted_at'];

    protected $fillable = [
        'sale_return_id',
        'product_id',
        'product_variant_id',
        'sale_id',
        'quantity',
        'unit_price',
        'serial_number',
        'discount_type',
        'discount_amount',
        'note',
        'updated_at',
    ];

    public $casts = [
        'sale_return_id' => 'integer',
        'product_id' => 'integer',
        'product_variant_id' => 'integer',
        'sale_id' => 'integer',
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
    ];

    public function saleReturn(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(SaleReturn::class);
    }

    public function sale(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function product(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productVariant(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }
}

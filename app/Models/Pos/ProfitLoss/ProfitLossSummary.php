<?php

namespace App\Models\Pos\ProfitLoss;

use App\Models\Pos\Product\Product;
use App\Models\Pos\Product\ProductVariant;
use App\Models\Pos\Sale\Sale;
use App\Models\TenantBaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfitLossSummary extends TenantBaseModel
{
    protected $fillable = [
        'sale_id',
        'tenant_id',
        'product_id',
        'product_variant_id',
        'sale_date',
        'full_invoice_number',
        'quantity',
        'sale_unit_price',
        'purchase_unit_price',
        'discount_type',
        'discount_amount',
        'total_sale_price',
        'total_purchase_price',
        'profit_loss',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }
}

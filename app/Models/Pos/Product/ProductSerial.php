<?php

namespace App\Models\Pos\Product;

use App\Models\Common\BaseModel;
use App\Models\Tenant\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductSerial extends BaseModel
{
    #[\Override]
    protected $fillable = [
        'serial_number',
        'product_variant_id',
        'product_id',
        'status',
        'max_retail_price',
        'tenant_id',
        'generated_by',
    ];

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id', 'id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    public function tenantProductSerials()
    {
        return $this->hasMany(TenantProductSerial::class, 'product_serial_id', 'id');
    }

    public function generatedBy()
    {
        return $this->belongsTo(User::class, 'generated_by', 'id');
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id', 'id');
    }
}

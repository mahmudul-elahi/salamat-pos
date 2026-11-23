<?php

namespace App\Models\Pos\Product;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductAttribute extends Model
{
    #[\Override]
    protected $fillable = [
        'product_id', 'attribute_id',
        'updated_at',
    ];

    #[\Override]
    public $casts = [
        'product_id' => 'integer',
        'attribute_id' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }
}

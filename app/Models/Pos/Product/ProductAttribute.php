<?php

namespace App\Models\Pos\Product;

use Illuminate\Database\Eloquent\Model;

class ProductAttribute extends Model
{

    protected $fillable = [
        'product_id', 'attribute_id',
        'updated_at',
    ];

    public $casts = [
        'product_id' => 'integer',
        'attribute_id' => 'integer',
    ];

    public function product(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function attribute(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }
}

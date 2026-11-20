<?php

namespace App\Models\Pos\Product;

use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model
{
    protected $fillable = [
        'product_id',
        'attribute_value_id',
        'image_path',
        'alt_text',
        'order',
        'is_primary'
    ];

    public $casts = [
        'attribute_value_id' => 'integer',
        'product_id' => 'integer',
        'is_primary' => 'boolean',
        'order' => 'integer'
    ];

    public function product(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function attributeValue(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(AttributeValue::class);
    }
}

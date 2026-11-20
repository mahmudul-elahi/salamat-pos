<?php

namespace App\Models\Pos\Product;

use Illuminate\Database\Eloquent\Model;

class ProductVideo extends Model
{
    protected $fillable = [
        'product_id',
        'attribute_value_id',
        'video_link',
        'order',
    ];

    public $casts = [
        'product_id' => 'integer',
        'attribute_value_id' => 'integer',
        'order' => 'integer',
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

<?php

namespace App\Models\Pos\Product;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVideo extends Model
{
    #[\Override]
    protected $fillable = [
        'product_id',
        'attribute_value_id',
        'video_link',
        'order',
    ];

    #[\Override]
    public $casts = [
        'product_id' => 'integer',
        'attribute_value_id' => 'integer',
        'order' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function attributeValue(): BelongsTo
    {
        return $this->belongsTo(AttributeValue::class);
    }
}

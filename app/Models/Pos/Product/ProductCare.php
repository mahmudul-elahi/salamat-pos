<?php

namespace App\Models\Pos\Product;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductCare extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'product_id',
        'care_id',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function care(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Pos\Care\Care::class);
    }
}

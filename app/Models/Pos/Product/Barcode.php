<?php

namespace App\Models\Pos\Product;

use App\Models\Pos\Product\Product;
use Illuminate\Database\Eloquent\Model;

class Barcode extends Model
{
    protected $fillable = [
        'product_id',
        'barcode_number',
        'use_code',
        'serial_number',
        'number',
    ];
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
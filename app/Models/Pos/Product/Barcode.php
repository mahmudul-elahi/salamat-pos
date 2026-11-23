<?php

declare(strict_types=1);

namespace App\Models\Pos\Product;

use Illuminate\Database\Eloquent\Model;

class Barcode extends Model
{
    #[\Override]
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

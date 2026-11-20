<?php

declare(strict_types=1);

namespace App\Models\Ecommerce\Warranty;

use App\Models\Common\BaseModel;
use App\Models\Pos\Product\Product;

class Warranty extends BaseModel
{
    protected $table = 'claims';

    protected $fillable = [
        'name',
        'phone',
        'message',
        'attachments',
        'others',
        'status',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}

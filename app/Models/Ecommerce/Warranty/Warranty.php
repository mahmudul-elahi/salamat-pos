<?php

namespace App\Models\Ecommerce\Warranty;

use App\Models\Common\BaseModel;
use App\Models\Pos\Product\Product;
use Illuminate\Database\Eloquent\Model;

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

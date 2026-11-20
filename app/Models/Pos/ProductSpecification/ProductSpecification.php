<?php

namespace App\Models\Pos\ProductSpecification;

use App\Models\Pos\Product\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Pos\SpecificationGroup\SpecificationGroup;

class ProductSpecification extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'parent_id',
        'sub_id',
        'details',
    ];

    // Relation to the product
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function parent()
    {
        return $this->belongsTo(\App\Models\Pos\SpecificationGroup\SpecificationGroup::class, 'parent_id', 'id');
    }

    public function sub()
    {
        return $this->belongsTo(\App\Models\Pos\SpecificationGroup\SpecificationGroup::class, 'sub_id', 'id');
    }
}

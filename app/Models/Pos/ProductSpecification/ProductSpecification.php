<?php

declare(strict_types=1);

namespace App\Models\Pos\ProductSpecification;

use App\Models\Pos\Product\Product;
use App\Models\Pos\SpecificationGroup\SpecificationGroup;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductSpecification extends Model
{
    use HasFactory;

    #[\Override]
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
        return $this->belongsTo(SpecificationGroup::class, 'parent_id', 'id');
    }

    public function sub()
    {
        return $this->belongsTo(SpecificationGroup::class, 'sub_id', 'id');
    }
}

<?php

namespace App\Filters\Landlord\Product;

use App\Filters\BaseFilter;
use App\Filters\Landlord\Traits\CategoryFilterTrait;
use Illuminate\Database\Eloquent\Builder;

class ProductFilter extends BaseFilter
{
    use CategoryFilterTrait;

    public function search($search = null): void
    {
        $this->builder->when($search, function ($query, $search) {
            $words = array_filter(explode(' ', trim($search)));
            foreach ($words as $word) {
                $query->where('product_name', 'LIKE', '%' . $word . '%');
            }
        });
    }

    public function unit($ids = null): void
    {
        $unit = explode(',', $ids);

        $this->builder->when($ids, fn(Builder $query) => $query->whereHas('unit', fn(Builder $query) => $query->whereIn('unit_id', $unit)));
    }

    public function brand($ids = null): void
    {
        $brand = explode(',', $ids);

        $this->builder->when($ids, fn(Builder $query) => $query->whereHas('brand', fn(Builder $query) => $query->whereIn('brand_id', $brand)));
    }
}

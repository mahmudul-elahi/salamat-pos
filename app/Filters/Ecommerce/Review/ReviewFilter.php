<?php

namespace App\Filters\Ecommerce\Review;

use App\Filters\BaseFilter;

class ReviewFilter extends BaseFilter
{
    public function product($productId = null): void
    {
        $productId = explode(',', $productId);

        $this->builder->when($productId, function ($query, $productId) {
            $query->whereIn('product_id', $productId);
        });
    }

    public function search($search = null): void
    {
        $this->builder->when($search, function ($query) use ($search) {
            $query->where(function ($query) use ($search) {
                $query->where('customer_name', 'LIKE', '%' . $search . '%')
                    ->orWhereHas('contact', function ($query) use ($search) {
                        $query->where('name', 'LIKE', '%' . $search . '%');
                    });
            });
        });
    }
}

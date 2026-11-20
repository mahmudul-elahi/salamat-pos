<?php

namespace App\Filters\Pos\Product;

use App\Filters\BaseFilter;
use App\Models\Landlord\Category\Category;

class ProductFilter extends BaseFilter
{

    public function search($search = null): void
    {
        $this->builder->when($search, function ($query, $search) {
            $words = array_filter(explode(' ', trim($search)));
            foreach ($words as $word) {
                $query->where('product_name', 'LIKE', '%' . $word . '%');
            }
        });
    }

    /**
     * Matches products directly on any of the selected categories plus
     * every child/grandchild category underneath them. Previously used
     * where('category_id', $categoryId) with $categoryId as an array,
     * which silently matched almost nothing instead of filtering properly.
     */
    public function category($categoryId = null): void
    {
        $this->builder->when($categoryId, function ($query) use ($categoryId) {
            $categoryIds = Category::query()
                ->whereIn('id', explode(',', $categoryId))
                ->get()
                ->flatMap(fn(Category $category) => $category->getAllDescendantIds())
                ->unique()
                ->values()
                ->all();

            $query->whereIn('category_id', $categoryIds);
        });
    }

    public function brand($brandId = null): void
    {
        $brandId = explode(',', $brandId);

        $this->builder->when($brandId, function ($query, $brandId) {
            $query->whereIn('brand_id', $brandId);
        });
    }

    public function status($status = null): void
    {
        if ($status === null || $status === '') {
            return;
        }

        $status = array_map('intval', explode(',', $status));

        $this->builder->when($status, function ($query, $status) {
            $query->whereIn('status', $status)
                ->orWhereIn('a_status', $status);
        });
    }

    public function isEcommerce($value = null): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $this->builder->where('is_ecommerce', filter_var($value, FILTER_VALIDATE_BOOLEAN));
    }
}

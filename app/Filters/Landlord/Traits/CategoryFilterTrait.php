<?php

namespace App\Filters\Landlord\Traits;

use App\Models\Landlord\Category\Category;
use Illuminate\Database\Eloquent\Builder;

trait CategoryFilterTrait
{
    /**
     * Matches products directly on any of the selected categories plus
     * every child/grandchild category underneath them — picking a parent
     * category should surface its descendants' products too, same as the
     * Stock Report's category filter.
     */
    public function category($ids = null): void
    {
        $this->builder->when($ids, function (Builder $query) use ($ids) {
            $categoryIds = Category::query()
                ->whereIn('id', explode(',', $ids))
                ->get()
                ->flatMap(fn(Category $category) => $category->getAllDescendantIds())
                ->unique()
                ->values()
                ->all();

            $query->whereIn('category_id', $categoryIds);
        });
    }
}

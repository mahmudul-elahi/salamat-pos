<?php

namespace App\Filters\Pos\Report;

use App\Exceptions\GeneralException;
use App\Filters\BaseFilter;
use App\Models\Landlord\Category\Category;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class StockReportFilter extends BaseFilter
{
    public function search($search = null): void
    {
        $this->builder->when($search, function ($query, $search) {
            $query->where('products.product_name', 'like', '%' . $search . '%')
                ->orWhereHas('serial', function ($query) use ($search) {
                    $query->where('serial_number', 'like', '%' . $search . '%');
                });
        });
    }

    // public function brand($ids = null): void
    // {
    //     $brand = explode(',', $ids);

    //     $this->builder->when($ids, fn(Builder $query) => $query->whereHas('brand', fn(Builder $query) => $query->whereIn('brand_id', $brand)));
    // }

    // public function category($ids = null): void
    // {
    //     $category = explode(',', $ids);

    //     $this->builder->when($ids, fn(Builder $query) => $query->whereHas('category', fn(Builder $query) => $query->whereIn('category_id', $category)));
    // }
    public function brand($ids = null): void
    {
        $brand = explode(',', $ids);

        $this->builder->when($ids, function (Builder $query) use ($brand) {
            $query->whereIn('products.brand_id', $brand);
        });
    }

    public function category($ids = null): void
    {
        // Filtering by a parent category should also pull in its children's
        // and grandchildren's stock, not just products directly on that exact
        // category — so expand each selected id into itself + every descendant
        // (mirroring StockReportController's category handling).
        $categoryIds = $ids ? Category::query()
            ->whereIn('id', explode(',', $ids))
            ->get()
            ->flatMap(fn (Category $cat) => $cat->getAllDescendantIds())
            ->unique()
            ->values()
            ->all() : null;

        $this->builder->when($categoryIds, function (Builder $query) use ($categoryIds) {
            $query->whereIn('products.category_id', $categoryIds);
        });
    }


    public function warehouse($ids = null): void
    {
        // $warehouse = explode(',', $ids);

        // $this->builder->when($ids, fn(Builder $query) => $query->whereIn('tenant_id', $warehouse));
    }

    public function product($ids = null): void
    {

        $product = explode(',', $ids);

        $this->builder->when($ids, fn(Builder $query) => $query->whereIn('product_id', $product));
    }

    /**
     * @throws GeneralException
     */
    public function date($range = null)
    {
        if (empty($range)) {
            return;
        }

        // Handle JSON string input from query params
        if (is_string($range)) {
            $range = json_decode($range, true);
        }

        if (!is_array($range) || count($range) < 2) {
            return;
        }

        [$startDate, $endDate] = $range;

        try {
            $start = Carbon::parse($startDate)->startOfDay();
            $end = Carbon::parse($endDate)->endOfDay();
            return $this->builder->whereBetween('updated_at', [$start, $end]);
        } catch (\Exception $e) {
            throw new GeneralException('Invalid your date range');
        }
    }
}

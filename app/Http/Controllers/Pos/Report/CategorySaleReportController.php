<?php

namespace App\Http\Controllers\Pos\Report;

use App\Filters\Pos\Report\ReportNameFilter;
use App\Http\Controllers\Controller;
use App\Models\Landlord\Category\Category;
use Illuminate\Support\Facades\DB;

class CategorySaleReportController extends Controller
{
    public function index()
    {
        return Category::query()
            ->filters(new ReportNameFilter('categories.name'))
            ->leftJoin('products', 'categories.id', '=', 'products.category_id')
            ->leftJoin('sale_items', 'products.id', '=', 'sale_items.product_id')
            ->select(
                'categories.id',
                'categories.name as category_name',
                DB::raw("
            COALESCE(
                SUM(
                    CASE
                        WHEN sale_items.discount_type = 'percent'
                            THEN (sale_items.unit_price * sale_items.quantity)
                                 - ((sale_items.unit_price * sale_items.quantity) * sale_items.discount_amount / 100)
                        WHEN sale_items.discount_type = 'fixed'
                            THEN (sale_items.unit_price * sale_items.quantity)
                                 - sale_items.discount_amount
                        ELSE (sale_items.unit_price * sale_items.quantity)
                    END
                ),
            0) AS total_sales
        ")
            )
            ->groupBy('categories.id', 'categories.name')
            ->orderBy('categories.name')
            ->paginate(request('per_page', 10));
    }
}

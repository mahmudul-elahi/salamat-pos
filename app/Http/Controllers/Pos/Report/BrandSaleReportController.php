<?php

namespace App\Http\Controllers\Pos\Report;

use App\Filters\Pos\Report\ReportNameFilter;
use App\Http\Controllers\Controller;
use App\Models\Landlord\Brand\Brand;
use Illuminate\Support\Facades\DB;

class BrandSaleReportController extends Controller
{
    public function index()
    {
        return Brand::query()
            ->filters(new ReportNameFilter('brands.name'))
            ->leftJoin('products', 'brands.id', '=', 'products.brand_id')
            ->leftJoin('sale_items', 'products.id', '=', 'sale_items.product_id')
            ->select(
                'brands.id',
                'brands.name as brand_name',
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
            0) as total_sales
        ")
            )
            ->groupBy('brands.id', 'brands.name')
            ->orderBy('brands.name')
            ->paginate(request('per_page', 10));
    }
}

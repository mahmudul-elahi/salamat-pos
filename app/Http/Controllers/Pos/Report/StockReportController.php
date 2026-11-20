<?php

namespace App\Http\Controllers\Pos\Report;

use App\Filters\Pos\Product\ProductFilter;
use App\Http\Controllers\Controller;
use App\Models\Landlord\Category\Category;
use App\Models\Pos\Sale\SaleItem;
use App\Services\Pos\Report\Stock\StockReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class StockReportController extends Controller
{
    public function __construct(protected StockReportService $service) {}

    /**
     * Stock is reported per purchase batch (purchase_id + product + variant)
     * instead of a pure product/variant aggregate, so each row can carry the
     * batch's own purchase date, supplier and invoice number. Every batch is
     * shown even once fully sold through, so purchased/sold/in-stock always
     * add up on each row (quantity means units from that batch still unsold —
     * status purchase/sale_return in tenant_product_serials).
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $wareHouse = $request->input('warehouse');
        $brand = $request->input('brand');
        $category = $request->input('category');
        $product = $request->input('product');
        $supplier = $request->input('supplier');
        $search = $request->input('search');
        $dateRange = $request->input('date');
        $isAppAdmin = auth()->user()->isAppAdmin();
        $tenantId = $isAppAdmin ? $wareHouse : tenant()->id;

        // Filtering by a parent category should also pull in its children's
        // and grandchildren's stock, not just products directly on that exact
        // category — so expand each selected id into itself + every descendant.
        $categoryIds = $category ? Category::query()
            ->whereIn('id', explode(',', $category))
            ->get()
            ->flatMap(fn (Category $cat) => $cat->getAllDescendantIds())
            ->unique()
            ->values()
            ->all() : null;

        // purchase_items.serial_number isn't guaranteed unique (the same unit
        // has been re-entered across unrelated purchases in this data), so a
        // serial that appears in several purchase_items rows would otherwise
        // get its current stock/sold status counted once per batch. Keeping
        // only the most recent purchase_items row per serial makes every
        // physical unit belong to exactly one batch, matching the single
        // current status tenant_product_serials actually holds for it.
        $latestPurchaseItems = DB::table('purchase_items')
            ->whereNull('deleted_at')
            ->whereNotNull('serial_number')
            ->select('product_id', 'product_variant_id', 'serial_number', DB::raw('MAX(id) as latest_id'))
            ->groupBy('product_id', 'product_variant_id', 'serial_number');

        $filteredQuery = DB::table('purchase_items as pi')
            ->joinSub($latestPurchaseItems, 'lpi', fn ($join) => $join->on('lpi.latest_id', '=', 'pi.id'))
            ->join('purchases as pur', 'pur.id', '=', 'pi.purchase_id')
            ->join('products as p', 'p.id', '=', 'pi.product_id')
            ->leftJoin('product_variants as pv', 'pv.id', '=', 'pi.product_variant_id')
            ->leftJoin('contacts as c', 'c.id', '=', 'pur.contact_id')
            ->leftJoin('brands as b', 'b.id', '=', 'p.brand_id')
            ->leftJoin('categories as cat', 'cat.id', '=', 'p.category_id')
            ->leftJoin('tenants as t', 't.id', '=', 'pur.tenant_id')
            ->leftJoin('product_serials as ps', function ($join) {
                $join->on('ps.product_id', '=', 'pi.product_id')
                    ->on('ps.product_variant_id', '=', 'pi.product_variant_id')
                    ->on('ps.serial_number', '=', 'pi.serial_number');
            })
            ->leftJoin('tenant_product_serials as tps', function ($join) use ($tenantId) {
                $join->on('tps.product_serial_id', '=', 'ps.id')
                    ->when($tenantId, fn ($j) => $j->where('tps.tenant_id', $tenantId));
            })
            ->when($tenantId, function ($q) use ($tenantId) {
                // A serial can be transferred to a different warehouse after
                // purchase, so "belongs to this tenant" can't be decided from
                // purchases.tenant_id alone — it has to check whether this
                // tenant currently (or ever) held that specific serial.
                $q->whereExists(function ($sub) use ($tenantId) {
                    $sub->select(DB::raw(1))
                        ->from('tenant_product_serials as tps2')
                        ->join('product_serials as ps2', 'ps2.id', '=', 'tps2.product_serial_id')
                        ->whereColumn('ps2.serial_number', 'pi.serial_number')
                        ->where('tps2.tenant_id', $tenantId);
                });
            })
            ->whereNull('pur.deleted_at')
            ->whereNull('pi.deleted_at')
            ->whereNotNull('pi.serial_number')
            ->when($brand, fn ($q) => $q->whereIn('p.brand_id', explode(',', $brand)))
            ->when($categoryIds, fn ($q) => $q->whereIn('p.category_id', $categoryIds))
            ->when($product, fn ($q) => $q->whereIn('p.id', explode(',', $product)))
            ->when($supplier, fn ($q) => $q->whereIn('pur.contact_id', explode(',', $supplier)))
            ->when($search, function ($q) use ($search) {
                $q->where(function ($query) use ($search) {
                    $query->where('p.product_name', 'like', "%{$search}%")
                        ->orWhere('pur.full_invoice_number', 'like', "%{$search}%")
                        ->orWhere('pi.serial_number', 'like', "%{$search}%");
                });
            })
            ->when($dateRange, function ($q) use ($dateRange) {
                $range = is_string($dateRange) ? json_decode($dateRange, true) : $dateRange;
                if (is_array($range) && count($range) >= 2) {
                    $start = Carbon::parse($range[0])->startOfDay();
                    $end = Carbon::parse($range[1])->endOfDay();
                    $q->whereBetween('pur.date', [$start, $end]);
                }
            });

        $baseQuery = (clone $filteredQuery)
            ->groupBy(
                'pi.purchase_id',
                'pi.product_id',
                'pi.product_variant_id'
            );

        // A single pass over every matching group gives us the rows, the
        // pagination count, and the footer totals at once. The dataset is
        // one row per purchase batch (not per serial), so it stays small
        // enough to page through in memory instead of hitting the DB twice
        // for the same expensive join + group by.
        $page = (int) $request->input('page', 1);

        $allGroups = (clone $baseQuery)
            ->select([
                'pi.purchase_id',
                'pi.product_id',
                'pi.product_variant_id',
                DB::raw('MAX(p.product_name) as product_name'),
                DB::raw('MAX(p.product_image) as product_image'),
                DB::raw('MAX(pv.sku) as variant_sku'),
                DB::raw('MAX(pur.date) as purchase_date'),
                DB::raw('MAX(pur.full_invoice_number) as full_invoice_number'),
                DB::raw('MAX(c.name) as supplier_name'),
                DB::raw('MAX(c.profile_picture) as supplier_image'),
                DB::raw('MAX(b.name) as brand_name'),
                DB::raw('MAX(cat.name) as category_name'),
                DB::raw('MAX(t.name) as warehouse_name'),
                DB::raw('COUNT(DISTINCT pi.serial_number) as purchased_qty'),
                DB::raw("COUNT(DISTINCT CASE WHEN tps.status IN ('purchase', 'sale_return') THEN pi.serial_number END) as quantity"),
                DB::raw("COUNT(DISTINCT CASE WHEN tps.status = 'sale' THEN pi.serial_number END) as sold_qty"),
            ])
            ->orderBy('pi.product_id', 'asc')
            ->get();

        $totalGroups = $allGroups->count();
        $grandPurchased = $allGroups->sum('purchased_qty');
        $grandQuantity = $allGroups->sum('quantity');
        $grandSold = $allGroups->sum('sold_qty');

        $rows = $allGroups->slice(($page - 1) * $perPage, $perPage)->values();

        $variantIds = $rows->pluck('product_variant_id')->unique()->filter()->values();
        $purchaseIds = $rows->pluck('purchase_id')->unique()->filter()->values();

        $variantAttributes = $variantIds->isEmpty() ? collect() : DB::table('variant_attribute_values as pvav')
            ->join('attribute_values as av', 'pvav.attribute_value_id', '=', 'av.id')
            ->whereIn('pvav.product_variant_id', $variantIds)
            ->select('pvav.product_variant_id', 'av.value')
            ->get()
            ->groupBy('product_variant_id');

        // Every serial purchased in the batch is listed, not just the ones
        // still in stock — a fully sold-out batch still shows the serial(s)
        // that made up its purchased_qty (matching how that count itself is
        // computed, with no status filter).
        $serialNumbers = $purchaseIds->isEmpty() ? collect() : DB::table('purchase_items as pi')
            ->joinSub($latestPurchaseItems, 'lpi', fn ($join) => $join->on('lpi.latest_id', '=', 'pi.id'))
            ->whereIn('pi.purchase_id', $purchaseIds)
            ->whereNull('pi.deleted_at')
            ->whereNotNull('pi.serial_number')
            ->select('pi.purchase_id', 'pi.product_id', 'pi.product_variant_id', 'pi.serial_number')
            ->get()
            ->groupBy(fn ($row) => "{$row->purchase_id}-{$row->product_id}-{$row->product_variant_id}");

        $startIndex = ($page - 1) * $perPage;

        $data = $rows->values()->map(function ($row, $index) use ($variantAttributes, $serialNumbers, $startIndex) {
            $groupKey = "{$row->purchase_id}-{$row->product_id}-{$row->product_variant_id}";

            return [
                'sl' => $startIndex + $index + 1,
                'product_name' => $row->product_name,
                'product_image' => $row->product_image,
                'variant_sku' => $row->variant_sku,
                'brand_name' => $row->brand_name,
                'category_name' => $row->category_name,
                'warehouse_name' => $row->warehouse_name ?? '-',
                'attribute_values' => $variantAttributes
                    ->get($row->product_variant_id, collect())
                    ->pluck('value')
                    ->implode('/'),
                'purchase_date' => $row->purchase_date,
                'supplier_name' => $row->supplier_name ?? '-',
                'supplier_image' => $row->supplier_image,
                'full_invoice_number' => $row->full_invoice_number,
                'purchased_qty' => (int) $row->purchased_qty,
                'quantity' => (int) $row->quantity,
                'sold_qty' => (int) $row->sold_qty,
                'serial_numbers' => $serialNumbers
                    ->get($groupKey, collect())
                    ->pluck('serial_number')
                    ->unique()
                    ->values()
                    ->toArray(),
            ];
        });

        $lastPage = (int) max(1, ceil($totalGroups / $perPage));

        return response()->json([
            'grand_total' => [
                'purchased_qty' => (int) $grandPurchased,
                'quantity' => (int) $grandQuantity,
                'sold_qty' => (int) $grandSold,
            ],
            'current_page' => $page,
            'data' => $data,
            'first_page_url' => null,
            'from' => $totalGroups ? $startIndex + 1 : null,
            'last_page' => $lastPage,
            'last_page_url' => null,
            'next_page_url' => $page < $lastPage ? '' : null,
            'path' => $request->url(),
            'per_page' => (int) $perPage,
            'prev_page_url' => $page > 1 ? '' : null,
            'to' => $totalGroups ? min($startIndex + $perPage, $totalGroups) : null,
            'total' => $totalGroups,
        ]);
    }

    public function saleByProduct()
    {
        return SaleItem::query()
            ->filters(new ProductFilter)
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->select([
                'products.id as product_id',
                'products.product_name',
                DB::raw('SUM(sale_items.quantity) as total_quantity'),
                DB::raw("
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
            ) AS total_sales
        "),
            ])
            ->groupBy('products.id')
            ->orderByDesc('total_sales')
            ->paginate(request('per_page', 10));
    }
}

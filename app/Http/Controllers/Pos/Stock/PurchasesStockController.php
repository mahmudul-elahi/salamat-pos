<?php

namespace App\Http\Controllers\Pos\Stock;

use App\Filters\Pos\Product\ProductSerialFilter;
use App\Http\Controllers\Controller;
use App\Models\Pos\Expense\Expense;
use App\Models\Pos\Income\Income;
use App\Models\Pos\Investment\Investment;
use App\Models\Pos\Product\Product;
use App\Models\Pos\Product\ProductVariant;
use App\Models\Pos\Product\TenantProductSerial;
use App\Models\Pos\ProfitLoss\ProfitLossSummary;
use App\Models\Pos\Sale\Sale;
use App\Scopes\TenantScope;
use Illuminate\Support\Facades\DB;

class PurchasesStockController extends Controller
{

    // public function purchaseStock()
    // {
    //     // ── 1. সব tenant এর current stock serials ─────────────────────────────
    //     $grouped = TenantProductSerial::query()
    //         ->withoutGlobalScope(TenantScope::class)
    //         ->whereIn('status', ['purchase', 'sale_return'])
    //         ->whereHas('productSerial', function ($query) {
    //             $query->filters(new ProductSerialFilter())
    //                 ->whereHas('variant', function ($q) {
    //                     $q->withTrashed();
    //                 });
    //         })
    //         ->with([
    //             'tenant' => function ($q) {
    //                 $q->select('id', 'name')
    //                     ->with('setting', function ($q) {
    //                         $q->where('name', 'company_logo');
    //                     });
    //             },

    //             'productSerial:id,serial_number,product_variant_id',
    //         ])
    //         ->get();

    //     // ── 2. Tenant IDs ──────────────────────────────────────────────────────
    //     $tenantIds = $grouped->pluck('tenant_id')->unique()->filter()->values();

    //     // ── 3. Total deposit per tenant — Investment table থেকে ───────────────
    //     $deposits = Investment::query()
    //         ->withoutGlobalScope(TenantScope::class)
    //         ->whereIn('tenant_id', $tenantIds)
    //         ->groupBy('tenant_id')
    //         ->selectRaw('tenant_id, SUM(amount) as total')
    //         ->pluck('total', 'tenant_id');

    //     // ── 4. Total withdrawal per tenant — Income table থেকে ────────────────
    //     $withdrawals = Income::query()
    //         ->withoutGlobalScope(TenantScope::class)
    //         ->whereIn('tenant_id', $tenantIds)
    //         ->groupBy('tenant_id')
    //         ->selectRaw('tenant_id, SUM(amount) as total')
    //         ->pluck('total', 'tenant_id');

    //     // ── 5. Total amount per tenant — purchase_items থেকে ──────────────────
    //     $totalAmounts = [];
    //     foreach ($tenantIds as $tenantId) {
    //         $result = DB::selectOne("
    //         SELECT SUM(pi.unit_price) as total
    //         FROM purchase_items pi
    //         WHERE pi.id IN (
    //             SELECT MAX(pi2.id)
    //             FROM tenant_product_serials tps
    //             INNER JOIN product_serials ps ON ps.id = tps.product_serial_id
    //             INNER JOIN purchase_items pi2 ON pi2.serial_number = ps.serial_number
    //             WHERE tps.tenant_id = ?
    //               AND tps.status IN ('purchase', 'sale_return')
    //             GROUP BY ps.serial_number
    //         )
    //     ", [$tenantId]);

    //         $totalAmounts[$tenantId] = $result->total ?? 0;
    //     }

    //     // ── 6. Group by tenant and build response ─────────────────────────────
    //     $data = $grouped
    //         ->groupBy('tenant_id')
    //         ->map(function ($tenantSerials) use ($deposits, $withdrawals, $totalAmounts) {
    //             $tenant   = $tenantSerials->first()->tenant;
    //             $tenantId = $tenant?->id;

    //             $uniqueSerials  = $tenantSerials->unique('product_serial_id');
    //             $totalDeposit   = (float) ($deposits[$tenantId]    ?? 0);
    //             $totalWithdrawal = (float) ($withdrawals[$tenantId] ?? 0);

    //             return [
    //                 'tenant'            => $tenant,
    //                 'total_qty'         => $uniqueSerials->count(),
    //                 'total_unit_price'  => $totalAmounts[$tenantId] ?? 0,
    //                 'total_deposit'     => $totalDeposit,
    //                 'total_withdrawal'  => $totalWithdrawal,
    //                 'total_balance'     => $totalDeposit - $totalWithdrawal,
    //             ];
    //         })
    //         ->values();

    //     return response()->json([
    //         'data' => $data,
    //     ]);
    // }

    public function purchaseStock()
    {
        // ── 1. Get current stock serials ─────────────────────────────
        $grouped = TenantProductSerial::query()
            ->withoutGlobalScope(TenantScope::class)
            ->whereIn('status', ['purchase', 'sale_return'])
            ->whereHas('productSerial', function ($query) {
                $query->filters(new ProductSerialFilter())
                    ->whereHas('variant', function ($q) {
                        $q->withTrashed();
                    });
            })
            ->with([
                'tenant' => function ($q) {
                    $q->select('id', 'name')
                        ->with('setting', function ($q) {
                            $q->where('name', 'company_logo');
                        });
                },
                'productSerial:id,serial_number,product_variant_id',
            ])
            ->get();

        // ── 2. Tenant IDs ──────────────────────────────────────────────────────
        $tenantIds = $grouped->pluck('tenant_id')
            ->unique()
            ->filter()
            ->values();

        // ── 3. Total deposit per tenant — Investment table ───────────────
        $deposits = Investment::query()
            ->whereIn('tenant_id', $tenantIds)
            ->groupBy('tenant_id')
            ->selectRaw('tenant_id, SUM(amount) as total')
            ->pluck('total', 'tenant_id');

        // ── 4. Total withdrawal per tenant — Income table ────────────────
        $withdrawals = Income::query()
            ->whereIn('tenant_id', $tenantIds)
            ->groupBy('tenant_id')
            ->selectRaw('tenant_id, SUM(amount) as total')
            ->pluck('total', 'tenant_id');

        // ── Expense ──────────────────────────────────────────────────────────
        $expenses = Expense::query()
            ->whereIn('tenant_id', $tenantIds)
            ->groupBy('tenant_id')
            ->selectRaw('tenant_id, SUM(amount) as total')
            ->pluck('total', 'tenant_id');

        // ── Total sale per tenant — sales table ─────────────────────────────
        $sales = Sale::query()
            ->whereIn('tenant_id', $tenantIds)
            ->groupBy('tenant_id')
            ->selectRaw('tenant_id, SUM(grand_total) as total')
            ->pluck('total', 'tenant_id');

        // ── Profit/Loss summary ─────────────────────────────────────────────
        $profitLossSummary = ProfitLossSummary::query()
            ->whereIn('tenant_id', $tenantIds)
            ->groupBy('tenant_id')
            ->selectRaw('tenant_id, SUM(profit_loss) as total')
            ->pluck('total', 'tenant_id');

        // ── 5. Total amount per tenant — purchase_items table (bulk, N+1 fix) ───
        $totalAmounts = [];

        if ($tenantIds->isNotEmpty()) {
            $placeholders = implode(',', array_fill(0, $tenantIds->count(), '?'));

            $rows = DB::select("
        SELECT tps.tenant_id, SUM(pi.unit_price) as total
        FROM purchase_items pi
        INNER JOIN (
            SELECT tps.tenant_id, ps.serial_number, MAX(pi2.id) as max_id
            FROM tenant_product_serials tps
            INNER JOIN product_serials ps ON ps.id = tps.product_serial_id
            INNER JOIN purchase_items pi2 ON pi2.serial_number = ps.serial_number
            WHERE tps.tenant_id IN ($placeholders)
              AND tps.status IN ('purchase', 'sale_return')
            GROUP BY tps.tenant_id, ps.serial_number
        ) tps ON tps.max_id = pi.id
        GROUP BY tps.tenant_id
    ", $tenantIds->all());

            foreach ($rows as $row) {
                $totalAmounts[$row->tenant_id] = $row->total ?? 0;
            }
        }

        // ── 6. Group by tenant and build response ─────────────────────────────
        $data = $grouped
            ->groupBy('tenant_id')
            ->map(function ($tenantSerials) use ($deposits, $withdrawals, $expenses, $sales, $profitLossSummary, $totalAmounts) {
                $tenant   = $tenantSerials->first()->tenant;
                $tenantId = $tenant?->id;

                $uniqueSerials   = $tenantSerials->unique('product_serial_id');
                $totalDeposit    = (float) ($deposits[$tenantId] ?? 0);
                $totalWithdrawal = (float) ($withdrawals[$tenantId] ?? 0);
                $totalExpense    = (float) ($expenses[$tenantId] ?? 0);
                $totalSale       = (float) ($sales[$tenantId] ?? 0);

                $profitLoss = (float) ($profitLossSummary[$tenantId] ?? 0) - $totalExpense;

                return [
                    'tenant'           => $tenant,
                    'total_qty'        => $uniqueSerials->count(),
                    'total_unit_price' => $totalAmounts[$tenantId] ?? 0,
                    'total_sale'       => $totalSale,
                    'total_deposit'    => $totalDeposit,
                    'total_withdrawal' => $totalWithdrawal,
                    'total_expense'    => $totalExpense,
                    'profit_loss'      => $profitLoss,
                    'total_balance'    => $totalDeposit - $totalWithdrawal,
                ];
            })
            ->values();

        // ── 7. Grand total (all tenants মিলিয়ে) ─────────────────────────────
        $grandTotal = [
            'total_unit_price' => (float) $data->sum('total_unit_price'),
            'total_sale'       => (float) $data->sum('total_sale'),
            'total_balance'    => (float) $data->sum('total_balance'),
            'total_expense'    => (float) $data->sum('total_expense'),
            'profit_loss'      => (float) $data->sum('profit_loss'),
        ];

        return response()->json([
            'data'        => $data,
            'grand_total' => $grandTotal,
        ]);
    }


    public function stockCalculation()
    {
        $wareHouse = request('warehouse');
        $product   = request('product');
        $perPage   = request('per_page', 15);

        // product filter condition — আছে তো যোগ হবে, না থাকলে খালি
        $productWhere = $product ? "AND purchase_items.product_id = ?" : "";
        $productWhere2 = $product ? "AND purchase_return_items.product_id = ?" : "";
        $productWhere3 = $product ? "AND sale_items.product_id = ?" : "";
        $productWhere4 = $product ? "AND sale_return_items.product_id = ?" : "";

        $sql = "
        SELECT product_id, product_variant_id, 'purchase' AS type, quantity AS qty
        FROM   purchase_items
        JOIN   purchases        ON purchases.id        = purchase_items.purchase_id
        WHERE  purchases.tenant_id = ?
        AND    purchases.deleted_at IS NULL
        AND    purchase_items.deleted_at IS NULL
        {$productWhere}

        UNION ALL

        SELECT product_id, product_variant_id, 'purchase_return' AS type, quantity AS qty
        FROM   purchase_return_items
        JOIN   purchase_returns ON purchase_returns.id = purchase_return_items.purchase_return_id
        WHERE  purchase_returns.tenant_id = ?
        AND    purchase_returns.deleted_at IS NULL
        AND    purchase_return_items.deleted_at IS NULL
        {$productWhere2}

        UNION ALL

        SELECT product_id, product_variant_id, 'sale' AS type, quantity AS qty
        FROM   sale_items
        JOIN   sales            ON sales.id            = sale_items.sale_id
        WHERE  sales.tenant_id = ?
        AND    sales.deleted_at IS NULL
        AND    sale_items.deleted_at IS NULL
        {$productWhere3}

        UNION ALL

        SELECT product_id, product_variant_id, 'sale_return' AS type, quantity AS qty
        FROM   sale_return_items
        JOIN   sale_returns     ON sale_returns.id     = sale_return_items.sale_return_id
        WHERE  sale_returns.tenant_id = ?
        AND    sale_returns.deleted_at IS NULL
        AND    sale_return_items.deleted_at IS NULL
        {$productWhere4}
    ";

        // binding — product আছে তো extra 4টা binding যোগ হবে
        $bindings = $product
            ? [$wareHouse, $product, $wareHouse, $product, $wareHouse, $product, $wareHouse, $product]
            : [$wareHouse, $wareHouse, $wareHouse, $wareHouse];

        $stocks = DB::table(DB::raw("({$sql}) AS stock_data"))
            ->select([
                'product_id',
                'product_variant_id',
                DB::raw("SUM(CASE WHEN type = 'purchase'        THEN qty ELSE 0 END) AS total_purchase"),
                DB::raw("SUM(CASE WHEN type = 'purchase_return' THEN qty ELSE 0 END) AS total_purchase_return"),
                DB::raw("SUM(CASE WHEN type = 'sale'            THEN qty ELSE 0 END) AS total_sale"),
                DB::raw("SUM(CASE WHEN type = 'sale_return'     THEN qty ELSE 0 END) AS total_sale_return"),
                DB::raw("SUM(CASE WHEN type IN ('purchase','sale_return')  THEN qty ELSE 0    END) AS total_in"),
                DB::raw("SUM(CASE WHEN type IN ('sale','purchase_return')  THEN qty ELSE 0    END) AS total_out"),
                DB::raw("SUM(CASE WHEN type IN ('purchase','sale_return')  THEN qty ELSE -qty END) AS net_stock"),
            ])
            ->addBinding($bindings, 'from')
            ->groupBy('product_id', 'product_variant_id')
            ->orderBy('product_id')
            ->paginate($perPage)
            ->withQueryString();

        $items      = collect($stocks->items());
        $productIds = $items->pluck('product_id')->unique()->filter()->values();
        $variantIds = $items->pluck('product_variant_id')->unique()->filter()->values();

        $products = Product::whereIn('id', $productIds)
            ->whereNull('deleted_at')
            ->pluck('product_name', 'id');

        $variants = ProductVariant::whereIn('id', $variantIds)
            ->whereNull('deleted_at')
            ->with('attributeValues:id,value')
            ->get()
            ->keyBy('id');

        $data = $items->map(function ($row) use ($products, $variants) {

            $variant          = $variants[$row->product_variant_id] ?? null;
            $attributeDisplay = null;

            if ($variant && $variant->attributeValues->isNotEmpty()) {
                $attributeDisplay = $variant->attributeValues
                    ->pluck('value')
                    ->implode('/');
            }

            return [
                'product_id'            => $row->product_id,
                'product_name'          => $products[$row->product_id] ?? null,
                'variant_id'            => $row->product_variant_id,
                'variant_sku'           => $variant?->sku,
                'variant_attributes'    => $attributeDisplay,
                'total_purchase'        => (float) $row->total_purchase,
                'total_purchase_return' => (float) $row->total_purchase_return,
                'total_sale'            => (float) $row->total_sale,
                'total_sale_return'     => (float) $row->total_sale_return,
                'total_in'              => (float) $row->total_in,
                'total_out'             => (float) $row->total_out,
                'net_stock'             => (float) $row->net_stock,
            ];
        });

        return response()->json([
            ...$stocks->toArray(),
            'data' => $data,
        ]);
    }
}

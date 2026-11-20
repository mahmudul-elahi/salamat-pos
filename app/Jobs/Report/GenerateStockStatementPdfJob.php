<?php

namespace App\Jobs\Report;

use App\Exceptions\GeneralException;
use App\Models\Landlord\Category\Category;
use App\Models\Pos\Product\Product;
use App\Services\Setting\SettingService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * A stock statement PDF can span thousands of product/variant rows, so —
 * mirroring GenerateStockReportPdfJob — it's built off the request/response
 * cycle: the controller dispatches this job and hands back a token, the
 * frontend polls stock-statement-pdf-status/{token} until this job's cache
 * entry flips to completed/failed, then downloads the file directly.
 */
class GenerateStockStatementPdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;
    public int $tries = 1;

    public function __construct(
        public string $token,
        public ?int $tenantId,
        public array $filters,
    ) {
        $this->onQueue('report-generate');
    }

    public static function cacheKey(string $token): string
    {
        return "stock-statement-pdf:{$token}";
    }

    /**
     * This is a per-product/variant net-stock aggregate (not per purchase
     * batch like Stock Report), so the row count stays far smaller for the
     * same dataset — a generous cap is enough here.
     */
    private const MAX_ROWS = 3000;

    /**
     * SL, product name, and the two stock-quantity columns are always
     * shown — everything else the user can opt out of from the column
     * picker. Order here is display order; widths are weights that get
     * normalized to 100% over whichever columns end up visible, so hiding
     * a column hands its space to the rest instead of leaving it blank.
     */
    private const COLUMN_DEFS = [
        'sl'                  => ['label' => 'SL', 'weight' => 4, 'align' => 'center', 'mandatory' => true],
        'product_name'        => ['label' => 'Product Name', 'weight' => 14, 'mandatory' => true],
        'brand'               => ['label' => 'Brand', 'weight' => 6],
        'category'            => ['label' => 'Category', 'weight' => 9],
        'purchase_date'       => ['label' => 'Purchase Date', 'weight' => 8],
        'supplier_name'       => ['label' => 'Supplier', 'weight' => 8],
        'full_invoice_number' => ['label' => 'Invoice No.', 'weight' => 8],
        'warehouse_name'      => ['label' => 'Warehouse', 'weight' => 10],
        'serial_numbers'      => ['label' => 'Serial Number', 'weight' => 17],
        'in_stock'            => ['label' => 'In Stock', 'weight' => 8, 'align' => 'center', 'mandatory' => true],
    ];

    private function resolveColumns(array $selectedKeys): array
    {
        $visible = array_filter(
            self::COLUMN_DEFS,
            fn($def, $key) => ($def['mandatory'] ?? false) || in_array($key, $selectedKeys, true),
            ARRAY_FILTER_USE_BOTH
        );

        $totalWeight = array_sum(array_column($visible, 'weight'));

        return array_map(fn($def) => [
            'label' => $def['label'],
            'align' => $def['align'] ?? null,
            'width' => round($def['weight'] / $totalWeight * 100, 2),
        ], $visible);
    }

    public function handle(): void
    {
        Cache::put(self::cacheKey($this->token), ['status' => 'processing'], now()->addHour());

        ini_set('memory_limit', '2048M');

        $path = $this->generate();

        Cache::put(self::cacheKey($this->token), [
            'status' => 'completed',
            'url' => Storage::disk('public')->url($path),
        ], now()->addHour());
    }

    public function failed(Throwable $exception): void
    {
        Cache::put(self::cacheKey($this->token), [
            'status' => 'failed',
            'message' => $exception->getMessage(),
        ], now()->addHour());
    }

    private function generate(): string
    {
        $tenantId  = $this->tenantId;
        $brand     = $this->filters['brand'] ?? null;
        $category  = $this->filters['category'] ?? null;
        $product   = $this->filters['product'] ?? null;
        $supplier  = $this->filters['supplier'] ?? null;
        $dateRange = $this->filters['date'] ?? null;
        $columns   = $this->resolveColumns($this->filters['columns'] ?? []);

        $supplierIds = $supplier ? explode(',', $supplier) : null;

        // Same descendant expansion as the on-screen statement — a parent
        // category filter should include its children's/grandchildren's stock.
        $categoryIds = $category ? Category::query()
            ->whereIn('id', explode(',', $category))
            ->get()
            ->flatMap(fn(Category $cat) => $cat->getAllDescendantIds())
            ->unique()
            ->values()
            ->all() : null;

        // ── 1. Stock grouped by product + variant ───────────────────────────
        $stockQuery = DB::table('tenant_product_serials as tps')
            ->join('product_serials as ps', 'tps.product_serial_id', '=', 'ps.id')
            ->when($tenantId, fn($q) => $q->where('tps.tenant_id', $tenantId))
            ->whereIn('tps.status', ['purchase', 'sale_return'])
            ->select([
                'ps.product_id',
                'ps.product_variant_id',
                DB::raw('COUNT(DISTINCT ps.serial_number) as qty'),
            ])
            ->groupBy('ps.product_id', 'ps.product_variant_id');

        // ── 2. Join products — same filter semantics as the on-screen table,
        // including its "date" filter meaning products.updated_at, not a
        // purchase date (kept consistent with StatementController::index()
        // and StockReportFilter::date()).
        $rows = Product::query()
            ->select(
                'products.id as product_id',
                'products.product_name',
                'brands.name as brand_name',
                'categories.name as category_name',
                'stock.product_variant_id',
                'stock.qty as net_stock'
            )
            ->joinSub($stockQuery, 'stock', fn($join) => $join->on('products.id', '=', 'stock.product_id'))
            ->leftJoin('brands', 'products.brand_id', '=', 'brands.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->when($brand, fn($q) => $q->whereIn('products.brand_id', explode(',', $brand)))
            ->when($categoryIds, fn($q) => $q->whereIn('products.category_id', $categoryIds))
            ->when($product, fn($q) => $q->whereIn('products.id', explode(',', $product)))
            ->when($supplierIds, function ($q) use ($supplierIds, $tenantId) {
                // Match a serial actually still held in stock, not just any
                // historical purchase line of this variant — see
                // StatementController::index() for the full rationale (same
                // "wrong supplier's name leaks onto a filtered row" bug).
                $q->whereExists(function ($sub) use ($supplierIds, $tenantId) {
                    $sub->selectRaw('1')
                        ->from('purchase_items as spi')
                        ->join('purchases as spur', 'spur.id', '=', 'spi.purchase_id')
                        ->join('product_serials as sps', function ($join) {
                            $join->on('sps.product_id', '=', 'spi.product_id')
                                ->on('sps.product_variant_id', '=', 'spi.product_variant_id')
                                ->on('sps.serial_number', '=', 'spi.serial_number');
                        })
                        ->join('tenant_product_serials as stps', function ($join) use ($tenantId) {
                            $join->on('stps.product_serial_id', '=', 'sps.id')
                                ->when($tenantId, fn($j) => $j->where('stps.tenant_id', $tenantId));
                        })
                        ->whereColumn('spi.product_id', 'products.id')
                        ->whereColumn('spi.product_variant_id', 'stock.product_variant_id')
                        ->whereIn('stps.status', ['purchase', 'sale_return'])
                        ->whereNull('spi.deleted_at')
                        ->whereNull('spur.deleted_at')
                        ->whereIn('spur.contact_id', $supplierIds);
                });
            })
            ->when($dateRange, function ($q) use ($dateRange) {
                $range = is_string($dateRange) ? json_decode($dateRange, true) : $dateRange;
                if (is_array($range) && count($range) >= 2) {
                    $start = Carbon::parse($range[0])->startOfDay();
                    $end = Carbon::parse($range[1])->endOfDay();
                    $q->whereBetween('products.updated_at', [$start, $end]);
                }
            })
            ->where('stock.qty', '>', 0)
            ->orderBy('products.id')
            ->get();

        if ($rows->isEmpty()) {
            throw new GeneralException('No stock data found');
        }

        if ($rows->count() > self::MAX_ROWS) {
            throw new GeneralException(
                'This statement has ' . $rows->count() . ' rows, which is too large to export as a PDF at once. '
                    . 'Please narrow it down with a brand, category, product, or supplier filter and try again.'
            );
        }

        $variantIds = $rows->pluck('product_variant_id')->unique()->filter()->values();

        // ── 3. Variant attribute values ──────────────────────────────────────
        $variantAttributes = DB::table('variant_attribute_values as pvav')
            ->join('attribute_values as av', 'pvav.attribute_value_id', '=', 'av.id')
            ->whereIn('pvav.product_variant_id', $variantIds)
            ->select('pvav.product_variant_id', 'av.value')
            ->get()
            ->groupBy('product_variant_id');

        // ── 4. Serial numbers (current stock only) ───────────────────────────
        $serials = DB::table('tenant_product_serials as tps')
            ->join('product_serials as ps', 'tps.product_serial_id', '=', 'ps.id')
            ->whereIn('ps.product_variant_id', $variantIds)
            ->whereIn('tps.status', ['purchase', 'sale_return'])
            ->when($tenantId, fn($q) => $q->where('tps.tenant_id', $tenantId))
            ->select('ps.product_variant_id', 'ps.serial_number')
            ->groupBy('ps.product_variant_id', 'ps.serial_number')
            ->get()
            ->groupBy('product_variant_id');

        // ── 5. Last restock info (date/supplier/invoice/warehouse/qty) ───────
        // Traced via the SERIAL NUMBERS this tenant currently physically
        // holds, not via purchase.tenant_id — a serial can be transferred to
        // a different warehouse after purchase, so a warehouse that received
        // stock only via transfer would otherwise show no restock info at
        // all despite clearly holding it.
        $currentSerialsForRestock = DB::table('tenant_product_serials as tps')
            ->join('product_serials as ps', 'tps.product_serial_id', '=', 'ps.id')
            ->whereIn('ps.product_variant_id', $variantIds)
            ->whereIn('tps.status', ['purchase', 'sale_return'])
            ->when($tenantId, fn($q) => $q->where('tps.tenant_id', $tenantId))
            ->select('ps.product_variant_id', 'ps.serial_number')
            ->distinct();

        $latestPurchaseItemIds = DB::table('purchase_items as pi')
            ->joinSub($currentSerialsForRestock, 'cs', function ($join) {
                $join->on('cs.product_variant_id', '=', 'pi.product_variant_id')
                    ->on('cs.serial_number', '=', 'pi.serial_number');
            })
            ->when($supplierIds, function ($q) use ($supplierIds) {
                // Without this, the "last restock" shown would be the
                // globally latest purchase for the variant even when a
                // supplier filter is active.
                $q->join('purchases as rpur', 'rpur.id', '=', 'pi.purchase_id')
                    ->whereIn('rpur.contact_id', $supplierIds);
            })
            ->whereNull('pi.deleted_at')
            ->select('pi.product_variant_id', DB::raw('MAX(pi.id) as latest_id'))
            ->groupBy('pi.product_variant_id');

        $latestPurchaseInfo = DB::table('purchase_items as pi')
            ->joinSub($latestPurchaseItemIds, 'lpi', fn($join) => $join->on('lpi.latest_id', '=', 'pi.id'))
            ->join('purchases as pur', 'pur.id', '=', 'pi.purchase_id')
            ->leftJoin('contacts as c', 'c.id', '=', 'pur.contact_id')
            ->leftJoin('tenants as t', 't.id', '=', 'pur.tenant_id')
            ->select(
                'pi.product_variant_id',
                'pi.purchase_id',
                'pur.date as purchase_date',
                'pur.full_invoice_number',
                'c.name as supplier_name',
                't.name as warehouse_name'
            )
            ->get()
            ->keyBy('product_variant_id');

        // "Quantity" = size of that same restock batch (how many serials
        // were purchased together on that invoice for this variant).
        $restockPurchaseIds = $latestPurchaseInfo->pluck('purchase_id')->unique()->filter()->values();

        // Purchase invoices are multi-line, so keying strictly by
        // product_variant_id (without purchase_id) let a variant's own
        // restock qty be sourced from a DIFFERENT variant's unrelated line
        // item on a shared invoice — must be keyed by the (variant,
        // purchase) pair actually chosen above.
        $batchQuantities = $restockPurchaseIds->isEmpty() ? collect() : DB::table('purchase_items')
            ->whereNull('deleted_at')
            ->whereIn('purchase_id', $restockPurchaseIds)
            ->whereIn('product_variant_id', $variantIds)
            ->select('product_variant_id', 'purchase_id', DB::raw('COUNT(DISTINCT serial_number) as qty'))
            ->groupBy('product_variant_id', 'purchase_id')
            ->get()
            ->keyBy(fn($row) => "{$row->product_variant_id}-{$row->purchase_id}");

        // ── 6. Build flat data for the PDF ────────────────────────────────────
        $sl = 1;
        $data = collect();
        $grandPurchased = 0;
        $grandInStock = 0;

        foreach ($rows as $row) {
            $vId = $row->product_variant_id;

            $variantSerials = $serials->get($vId, collect());
            $inStock = $variantSerials->pluck('serial_number')->unique()->count();
            $lastPurchase = $latestPurchaseInfo->get($vId);
            $purchasedQty = $lastPurchase
                ? (int) ($batchQuantities->get("{$vId}-{$lastPurchase->purchase_id}")->qty ?? 0)
                : 0;

            $grandInStock += $inStock;
            $grandPurchased += $purchasedQty;

            $data->push([
                'product_id'          => $row->product_id,
                'sl'                  => $sl++,
                'product_name'        => $row->product_name,
                'brand'               => $row->brand_name ?? '-',
                'category'            => $row->category_name ?? '-',
                'attribute_values'    => $variantAttributes
                    ->get($vId, collect())
                    ->pluck('value')
                    ->implode('/'),
                'purchase_date'       => $lastPurchase->purchase_date ?? null,
                'supplier_name'       => $lastPurchase->supplier_name ?? '-',
                'full_invoice_number' => $lastPurchase->full_invoice_number ?? null,
                'warehouse_name'      => $lastPurchase->warehouse_name ?? '-',
                'serial_numbers'      => $variantSerials
                    ->pluck('serial_number')
                    ->unique()
                    ->values()
                    ->toArray(),
                'purchased_qty'       => $purchasedQty,
                'in_stock'            => $inStock,
            ]);
        }

        $pdf = Pdf::loadView('pdf.stock-statement', [
            'stocks'         => $data,
            'columns'        => $columns,
            'date'           => Carbon::now()->format('d-m-Y'),
            'time'           => Carbon::now()->format('H:i:s'),
            'total_quantity' => $grandPurchased,
            'total_in_stock' => $grandInStock,
            'shop_info'      => $tenantId ? DB::table('tenants')->where('id', $tenantId)->first() : null,
            'tenantSettings' => resolve(SettingService::class)->getFormattedSettings('tenant', $tenantId),
        ])->setPaper('a4', 'portrait')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', false);

        $path = "stock-statements/{$this->token}.pdf";
        Storage::disk('public')->put($path, $pdf->output());

        return $path;
    }
}

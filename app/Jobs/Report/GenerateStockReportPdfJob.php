<?php

namespace App\Jobs\Report;

use App\Exceptions\GeneralException;
use App\Models\Landlord\Category\Category;
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
 * A stock report PDF can span thousands of purchase batches, so it's built
 * off the request/response cycle: the controller dispatches this job and
 * hands back a token, the frontend polls stock-report-pdf-status/{token}
 * until the cache entry this job writes flips to completed/failed, then
 * downloads the file from the public disk URL.
 */
class GenerateStockReportPdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;

    public int $tries = 1;

    public function __construct(
        public string $token,
        public ?int $tenantId,
        public array $filters,
    ) {
        // The production Supervisor worker only listens on
        // hrm-employee-invitation,hrm-leave-assign,report-generate — not
        // the default queue. Without this, the job silently sits pending
        // forever there and the frontend just times out (or, if some other
        // stray worker without this queue restriction picks it up under
        // different conditions, can produce a broken/incomplete PDF).
        $this->onQueue('report-generate');
    }

    public static function cacheKey(string $token): string
    {
        return "stock-report-pdf:{$token}";
    }

    /**
     * Above this many purchase-batch rows, dompdf's per-row rendering
     * overhead (measured at roughly 1.5-1.8MB/row for this table) risks
     * exhausting even a generous memory_limit — better to fail with a clear
     * message asking the user to narrow their filters than to let the
     * worker crash mid-render.
     */
    private const int MAX_ROWS = 1500;

    /**
     * SL, product name, and the three stock-quantity columns are always
     * shown — everything else the user can opt out of from the column
     * picker. Order here is display order; widths are weights that get
     * normalized to 100% over whichever columns end up visible, so hiding
     * a column hands its space to the rest instead of leaving it blank.
     */
    private const array COLUMN_DEFS = [
        'sl' => ['label' => 'SL', 'weight' => 3, 'align' => 'center', 'mandatory' => true],
        'product_name' => ['label' => 'Product Name', 'weight' => 9, 'mandatory' => true],
        'brand' => ['label' => 'Brand', 'weight' => 6],
        'category' => ['label' => 'Category', 'weight' => 9],
        'purchase_date' => ['label' => 'Purchase Date', 'weight' => 9],
        'supplier_name' => ['label' => 'Supplier', 'weight' => 7],
        'full_invoice_number' => ['label' => 'Invoice No.', 'weight' => 9],
        'warehouse_name' => ['label' => 'Warehouse', 'weight' => 18],
        'serial_numbers' => ['label' => 'Serial Number', 'weight' => 14],
        'total' => ['label' => 'Total', 'weight' => 5, 'align' => 'center', 'mandatory' => true],
        'sale' => ['label' => 'Sale', 'weight' => 4, 'align' => 'center', 'mandatory' => true],
        'in_stock' => ['label' => 'In Stock', 'weight' => 7, 'align' => 'center', 'mandatory' => true],
    ];

    private function resolveColumns(array $selectedKeys): array
    {
        $visible = array_filter(
            self::COLUMN_DEFS,
            fn ($def, $key) => ($def['mandatory'] ?? false) || in_array($key, $selectedKeys, true),
            ARRAY_FILTER_USE_BOTH
        );

        $totalWeight = array_sum(array_column($visible, 'weight'));

        return array_map(fn ($def) => [
            'label' => $def['label'],
            'align' => $def['align'] ?? null,
            'width' => round($def['weight'] / $totalWeight * 100, 2),
        ], $visible);
    }

    public function handle(): void
    {
        Cache::put(self::cacheKey($this->token), ['status' => 'processing'], now()->addHour());

        // dompdf holds the entire table's frame/cellmap tree in memory at
        // once, so a large stock report is a real memory hog — give this
        // job a lot more room than the default before it even starts.
        // Serial numbers are no longer capped per row, so this has more
        // headroom than the ~1.9GB measured at MAX_ROWS with full lists.
        ini_set('memory_limit', '3584M');

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
        $tenantId = $this->tenantId;
        $brand = $this->filters['brand'] ?? null;
        $category = $this->filters['category'] ?? null;
        $product = $this->filters['product'] ?? null;
        $supplier = $this->filters['supplier'] ?? null;
        $dateRange = $this->filters['date'] ?? null;
        $columns = $this->resolveColumns($this->filters['columns'] ?? []);

        // Same descendant expansion as the on-screen report — a parent
        // category filter should include its children's/grandchildren's stock.
        $categoryIds = $category ? Category::query()
            ->whereIn('id', explode(',', $category))
            ->get()
            ->flatMap(fn (Category $cat) => $cat->getAllDescendantIds())
            ->unique()
            ->values()
            ->all() : null;

        $latestPurchaseItems = DB::table('purchase_items')
            ->whereNull('deleted_at')
            ->whereNotNull('serial_number')
            ->select('product_id', 'product_variant_id', 'serial_number', DB::raw('MAX(id) as latest_id'))
            ->groupBy('product_id', 'product_variant_id', 'serial_number');

        $baseQuery = DB::table('purchase_items as pi')
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
            ->when($dateRange, function ($q) use ($dateRange) {
                $range = is_string($dateRange) ? json_decode($dateRange, true) : $dateRange;
                if (is_array($range) && count($range) >= 2) {
                    $start = Carbon::parse($range[0])->startOfDay();
                    $end = Carbon::parse($range[1])->endOfDay();
                    $q->whereBetween('pur.date', [$start, $end]);
                }
            })
            ->groupBy('pi.purchase_id', 'pi.product_id', 'pi.product_variant_id');

        $batches = (clone $baseQuery)
            ->select([
                'pi.purchase_id',
                'pi.product_id',
                'pi.product_variant_id',
                DB::raw('MAX(p.product_name) as product_name'),
                DB::raw('MAX(pv.sku) as variant_sku'),
                DB::raw('MAX(pur.date) as purchase_date'),
                DB::raw('MAX(pur.full_invoice_number) as full_invoice_number'),
                DB::raw('MAX(c.name) as supplier_name'),
                DB::raw('MAX(b.name) as brand_name'),
                DB::raw('MAX(cat.name) as category_name'),
                DB::raw('MAX(t.name) as warehouse_name'),
                DB::raw('COUNT(DISTINCT pi.serial_number) as purchased_qty'),
                DB::raw("COUNT(DISTINCT CASE WHEN tps.status IN ('purchase', 'sale_return') THEN pi.serial_number END) as quantity"),
                DB::raw("COUNT(DISTINCT CASE WHEN tps.status = 'sale' THEN pi.serial_number END) as sold_qty"),
            ])

            ->orderBy('pi.product_id', 'asc')
            ->get();

        if ($batches->isEmpty()) {
            throw new GeneralException('No stock data found');
        }

        if ($batches->count() > self::MAX_ROWS) {
            throw new GeneralException(
                'This report has '.$batches->count().' rows, which is too large to export as a PDF at once. '
                    .'Please narrow it down with a date range, brand, category, or supplier filter and try again.'
            );
        }

        $variantIds = $batches->pluck('product_variant_id')->unique()->filter()->values();
        $purchaseIds = $batches->pluck('purchase_id')->unique()->filter()->values();

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

        $sl = 1;
        $grandPurchased = 0;
        $grandSold = 0;
        $grandQuantity = 0;

        $data = $batches->map(function ($row) use (&$sl, &$grandPurchased, &$grandSold, &$grandQuantity, $variantAttributes, $serialNumbers) {
            $groupKey = "{$row->purchase_id}-{$row->product_id}-{$row->product_variant_id}";

            $grandPurchased += (int) $row->purchased_qty;
            $grandSold += (int) $row->sold_qty;
            $grandQuantity += (int) $row->quantity;

            return [
                'product_id' => $row->product_id,
                'sl' => $sl++,
                'product_name' => $row->product_name,
                'brand' => $row->brand_name ?? '-',
                'category' => $row->category_name ?? '-',
                'warehouse_name' => $row->warehouse_name ?? '-',
                'purchase_date' => $row->purchase_date,
                'supplier_name' => $row->supplier_name ?? '-',
                'full_invoice_number' => $row->full_invoice_number,
                'attribute_values' => $variantAttributes
                    ->get($row->product_variant_id, collect())
                    ->pluck('value')
                    ->implode('/'),
                'serial_numbers' => $serialNumbers
                    ->get($groupKey, collect())
                    ->pluck('serial_number')
                    ->unique()
                    ->values()
                    ->toArray(),
                'total' => (int) $row->purchased_qty,
                'sale' => (int) $row->sold_qty,
                'in_stock' => (int) $row->quantity,
            ];
        });

        $pdf = Pdf::loadView('pdf.stock-report', [
            'stocks' => $data,
            'columns' => $columns,
            'date' => Carbon::now()->format('d-m-Y'),
            'time' => Carbon::now()->format('H:i:s'),
            'total_quantity' => $grandPurchased,
            'total_sale' => $grandSold,
            'total_in_stock' => $grandQuantity,
            'shop_info' => $tenantId ? DB::table('tenants')->where('id', $tenantId)->first() : null,
            'tenantSettings' => resolve(SettingService::class)->getFormattedSettings('tenant', $tenantId),
        ])->setPaper('a4', 'portrait')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', false);

        $path = "stock-reports/{$this->token}.pdf";
        Storage::disk('public')->put($path, $pdf->output());

        return $path;
    }
}

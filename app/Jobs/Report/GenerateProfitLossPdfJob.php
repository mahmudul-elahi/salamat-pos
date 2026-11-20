<?php

namespace App\Jobs\Report;

use App\Exceptions\GeneralException;
use App\Models\Pos\ProfitLoss\ProfitLossSummary;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * A profit & loss statement can span thousands of sale line-items, so —
 * mirroring GenerateStockStatementPdfJob — it's built off the request/response
 * cycle: the controller dispatches this job and hands back a token, the
 * frontend polls profit-loss-pdf-status/{token} until this job's cache entry
 * flips to completed/failed, then downloads the file directly.
 */
class GenerateProfitLossPdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;
    public int $tries = 1;

    public function __construct(
        public string $token,
        public array $filters,
    ) {
        $this->onQueue('report-generate');
    }

    public static function cacheKey(string $token): string
    {
        return "profit-loss-pdf:{$token}";
    }

    /**
     * One row per sale line-item — a generous cap keeps the PDF from
     * ballooning into something DomPDF can't render in a reasonable time.
     */
    private const MAX_ROWS = 5000;

    /**
     * SL, date, invoice number, and the three money columns are always
     * shown — everything else the user can opt out of from the column
     * picker. Order here is display order; widths are weights that get
     * normalized to 100% over whichever columns end up visible.
     */
    private const COLUMN_DEFS = [
        'sl'                   => ['label' => 'SL', 'weight' => 4, 'align' => 'center', 'mandatory' => true],
        'date'                 => ['label' => 'Date', 'weight' => 8, 'mandatory' => true],
        'invoice_number'       => ['label' => 'Invoice Number', 'weight' => 10, 'mandatory' => true],
        'product'              => ['label' => 'Product', 'weight' => 16],
        'seller'               => ['label' => 'Seller', 'weight' => 10],
        'created_by'           => ['label' => 'Created By', 'weight' => 10],
        'payment_method'       => ['label' => 'Payment Method', 'weight' => 10],
        'status'               => ['label' => 'Status', 'weight' => 7, 'align' => 'center'],
        'total_sale_price'     => ['label' => 'Total Sale', 'weight' => 10, 'align' => 'right', 'mandatory' => true],
        'total_purchase_price' => ['label' => 'Total Purchase', 'weight' => 11, 'align' => 'right', 'mandatory' => true],
        'profit_loss'          => ['label' => 'Profit/Loss', 'weight' => 9, 'align' => 'right', 'mandatory' => true],
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
        $columns = $this->resolveColumns($this->filters['columns'] ?? []);

        $warehouseIds     = !empty($this->filters['warehouse']) ? explode(',', $this->filters['warehouse']) : null;
        $productIds       = !empty($this->filters['product']) ? explode(',', $this->filters['product']) : null;
        $sellerIds        = !empty($this->filters['seller']) ? explode(',', $this->filters['seller']) : null;
        $categoryIds      = !empty($this->filters['category']) ? explode(',', $this->filters['category']) : null;
        $brandIds         = !empty($this->filters['brand']) ? explode(',', $this->filters['brand']) : null;
        $paymentMethodIds = !empty($this->filters['payment_method']) ? explode(',', $this->filters['payment_method']) : null;
        $createdByIds     = !empty($this->filters['created_by']) ? explode(',', $this->filters['created_by']) : null;
        $status           = $this->filters['status'] ?? null;
        $dateRange        = $this->filters['date'] ?? null;

        // Mirrors ProfitLossFilter's own methods, kept as plain local
        // variables + ->when() (like GenerateStockStatementPdfJob) rather
        // than reused as a Filter object, since Filter::apply() reads off
        // the global request() helper, which a queued job shouldn't rely on.
        $baseQuery = ProfitLossSummary::query()
            ->when($warehouseIds, fn($q) => $q->whereIn('tenant_id', $warehouseIds))
            ->when($productIds, fn($q) => $q->whereIn('product_id', $productIds))
            ->when($sellerIds, function ($q) use ($sellerIds) {
                $q->whereHas('sale', fn($q) => $q->whereIn('seller_id', $sellerIds));
            })
            ->when($categoryIds, function ($q) use ($categoryIds) {
                $q->whereHas('product', fn($q) => $q->whereIn('category_id', $categoryIds));
            })
            ->when($brandIds, function ($q) use ($brandIds) {
                $q->whereHas('product', fn($q) => $q->whereIn('brand_id', $brandIds));
            })
            ->when($paymentMethodIds, function ($q) use ($paymentMethodIds) {
                $q->whereHas('sale.payments', fn($q) => $q->whereIn('payment_method_id', $paymentMethodIds));
            })
            ->when($createdByIds, function ($q) use ($createdByIds) {
                $q->whereHas('sale', fn($q) => $q->whereIn('created_by', $createdByIds));
            })
            ->when($status, function ($q) use ($status) {
                $q->whereHas('sale', fn($q) => $q->where('status', $status));
            })
            ->when($dateRange, function ($q) use ($dateRange) {
                $range = is_string($dateRange) ? json_decode($dateRange, true) : $dateRange;
                if (is_array($range) && count($range) >= 2) {
                    $start = Carbon::parse($range[0])->startOfDay();
                    $end = Carbon::parse($range[1])->endOfDay();
                    $q->whereBetween('sale_date', [$start, $end]);
                }
            });
            // Unlike ProfitLossFilter::date() (used by the on-screen table),
            // no date filter here means no date restriction at all — matching
            // GenerateStockStatementPdfJob, so picking any other single filter
            // (product/seller/category/...) is enough to generate the PDF.

        $totals = (clone $baseQuery)->selectRaw('
            COALESCE(SUM(total_sale_price), 0)     as grand_sale,
            COALESCE(SUM(total_purchase_price), 0) as grand_purchase,
            COALESCE(SUM(profit_loss), 0)           as grand_profit
        ')->first();

        $rowCount = (clone $baseQuery)->count();

        if ($rowCount === 0) {
            throw new GeneralException('No profit & loss data found');
        }

        if ($rowCount > self::MAX_ROWS) {
            throw new GeneralException(
                "This report has {$rowCount} rows, which is too large to export as a PDF at once. "
                    . 'Please narrow it down with a filter and try again.'
            );
        }

        $rows = $baseQuery
            ->select([
                'sale_id',
                'product_id',
                'product_variant_id',
                'full_invoice_number',
                'sale_date',
                'total_sale_price',
                'total_purchase_price',
                'profit_loss',
            ])
            ->with([
                'product:id,product_name',
                'productVariant:id,sku',
                'productVariant.attributeValues:id,attribute_id,value',
                'productVariant.attributeValues.attribute:id,name',
                'sale:id,seller_id,created_by,status',
                'sale.seller:id,name',
                'sale.createdBy:id,name',
                'sale.payments.paymentMethod:id,name',
            ])
            ->orderByDesc('sale_id')
            ->get();

        $data = $rows->values()->map(function ($row, $index) {
            return [
                'sl' => $index + 1,
                'date' => $row->sale_date,
                'invoice_number' => $row->full_invoice_number,
                'product_name' => $row->product?->product_name,
                'product_variant' => $row->productVariant?->attributeValues
                    ->pluck('value')
                    ->filter()
                    ->implode('/'),
                'seller' => $row->sale?->seller?->name,
                'created_by' => $row->sale?->createdBy?->name,
                'payment_method' => $row->sale?->payments
                    ->map(fn($payment) => $payment->paymentMethod?->name)
                    ->filter()
                    ->unique()
                    ->implode(', '),
                'status' => $row->sale?->status,
                'total_sale_price' => round((float) $row->total_sale_price, 2),
                'total_purchase_price' => round((float) $row->total_purchase_price, 2),
                'profit_loss' => round((float) $row->profit_loss, 2),
            ];
        });

        $pdf = Pdf::loadView('pdf.profit-loss', [
            'rows' => $data,
            'columns' => $columns,
            'date' => Carbon::now()->format('d-m-Y'),
            'time' => Carbon::now()->format('H:i:s'),
            'grand_total_sale' => round((float) $totals->grand_sale, 2),
            'grand_total_purchase' => round((float) $totals->grand_purchase, 2),
            'grand_profit_loss' => round((float) $totals->grand_profit, 2),
        ])->setPaper('a4', 'portrait')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', false);

        $path = "profit-loss-reports/{$this->token}.pdf";
        Storage::disk('public')->put($path, $pdf->output());

        return $path;
    }
}

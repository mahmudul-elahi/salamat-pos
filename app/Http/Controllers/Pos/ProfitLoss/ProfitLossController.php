<?php

namespace App\Http\Controllers\Pos\ProfitLoss;

use App\Filters\Pos\Report\ProfitLossFilter;
use App\Filters\Pos\Sale\SaleFilter;
use App\Http\Controllers\Controller;
use App\Jobs\Report\GenerateProfitLossPdfJob;
use App\Models\Pos\ProfitLoss\ProfitLossSummary;
use App\Services\Pos\Report\ProfitLoss\ProfitLossService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProfitLossController extends Controller
{
    public function __construct(protected ProfitLossService $service, protected ProfitLossFilter $filter)
    {
    }


    public function todayIndex(Request $request)
    {
        $today = \Illuminate\Support\Carbon::now('Asia/Dhaka')->toDateString();
        $request->merge(['date' => json_encode([$today, $today])]);

        return $this->index($request);
    }

    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 15);


        // ── Base query ────────────────────────────────────────────────────────
        $baseQuery = ProfitLossSummary::query()
            ->filters($this->filter);

        // ── Grand totals ──────────────────────────────────────────────────────
        $totals = (clone $baseQuery)->selectRaw('
        COALESCE(SUM(total_sale_price), 0)     as grand_sale,
        COALESCE(SUM(total_purchase_price), 0) as grand_purchase,
        COALESCE(SUM(profit_loss), 0)    as grand_profit
    ')->first();

        // ── Paginate ──────────────────────────────────────────────────────────
        $paginated = $baseQuery
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
                'product:id,product_name,product_image',
                'productVariant:id,sku',
                'productVariant.attributeValues:id,attribute_id,value',
                'productVariant.attributeValues.attribute:id,name',
                'sale:id,seller_id,created_by,status',
                'sale.seller:id,name',
                'sale.createdBy:id,name',
                'sale.payments.paymentMethod:id,name,logo',
            ])
            ->orderByDesc('sale_id')
            ->paginate($perPage);

        $offset = ($paginated->currentPage() - 1) * $paginated->perPage();

        $data = collect($paginated->items())->values()->map(function ($row, $index) use ($offset) {
            return [
                'sl' => $offset + $index + 1,
                'invoice_number' => $row->full_invoice_number,
                'date' => $row->sale_date,
                'product' => $row->product ? [
                    'name' => $row->product->product_name,
                    'image' => $row->product->product_image,
                    'variant' => $row->productVariant?->attributeValues
                        ->pluck('value')
                        ->filter()
                        ->values(),
                ] : null,
                'seller' => $row->sale?->seller ? [
                    'name' => $row->sale->seller->name,
                    'image' => $row->sale->seller->profile_picture,
                ] : null,
                'created_by' => $row->sale?->createdBy ? [
                    'name' => $row->sale->createdBy->name,
                    'image' => $row->sale->createdBy->profile_picture,
                ] : null,
                'status' => $row->sale?->status,
                'payment_method' => $row->sale?->payments
                    ->map(fn ($payment) => $payment->paymentMethod)
                    ->filter()
                    ->unique('id')
                    ->values()
                    ->map(fn ($pm) => ['name' => $pm->name, 'logo' => $pm->logo]),
                'total_sale_price' => round((float)$row->total_sale_price, 2),
                'total_purchase_price' => round((float)$row->total_purchase_price, 2),
                'profit_loss' => round((float)$row->profit_loss, 2),
            ];
        });

        return response()->json([
            'data' => $data,
            'current_page' => $paginated->currentPage(),
            'per_page' => $paginated->perPage(),
            'total' => $paginated->total(),
            'last_page' => $paginated->lastPage(),
            'grand_total' => [
                'total_sale_price' => round((float)$totals->grand_sale, 2),
                'total_purchase_price' => round((float)$totals->grand_purchase, 2),
                'profit_loss' => round((float)$totals->grand_profit, 2),
            ],
        ]);
    }

    public function generatePdf(Request $request)
    {
        $token = (string) Str::uuid();

        Cache::put(GenerateProfitLossPdfJob::cacheKey($token), ['status' => 'queued'], now()->addHour());

        $columns = $request->input('columns');

        GenerateProfitLossPdfJob::dispatch($token, [
            'warehouse' => $request->input('warehouse'),
            'product' => $request->input('product'),
            'seller' => $request->input('seller'),
            'category' => $request->input('category'),
            'brand' => $request->input('brand'),
            'payment_method' => $request->input('payment_method'),
            'created_by' => $request->input('created_by'),
            'status' => $request->input('status'),
            'date' => $request->input('date'),
            'columns' => $columns ? explode(',', $columns) : [],
        ])->onQueue('report-generate');

        return response()->json(['token' => $token]);
    }

    /**
     * Same as generatePdf(), but forces today's date (Asia/Dhaka) like
     * todayIndex() does, since Daily Closing's Profit Loss tab has no date
     * filter of its own — it's always scoped to "today".
     */
    public function generateTodayPdf(Request $request)
    {
        $today = \Illuminate\Support\Carbon::now('Asia/Dhaka')->toDateString();
        $request->merge(['date' => json_encode([$today, $today])]);

        return $this->generatePdf($request);
    }

    public function pdfStatus(string $token)
    {
        $status = Cache::get(GenerateProfitLossPdfJob::cacheKey($token));

        if (!$status) {
            return response()->json(['status' => 'not_found'], 404);
        }

        return response()->json($status);
    }

    /**
     * Called by the frontend right after it triggers the browser download,
     * so the generated PDF doesn't sit in storage forever.
     */
    public function pdfCleanup(string $token)
    {
        if (Cache::has(GenerateProfitLossPdfJob::cacheKey($token))) {
            Storage::disk('public')->delete("profit-loss-reports/{$token}.pdf");
            Cache::forget(GenerateProfitLossPdfJob::cacheKey($token));
        }

        return response()->json(['deleted' => true]);
    }

    public function statistic($rangeType = 6): array
    {
        // ---- Amounts ----
        $totalSaleAmount = $this->service->totalSaleAmount($rangeType);
        $totalPurchasesAmount = $this->service->totalPurchasesAmount($rangeType);
        $totalSaleReturnAmount = $this->service->totalSaleReturnAmount($rangeType);
        $totalPurchasesReturnAmount = $this->service->totalPurchasesReturnAmount($rangeType);

        // ---- Counts ----
        $totalSaleCount = $this->service->totalSaleCount($rangeType);
        $totalPurchasesCount = $this->service->totalPurchasesCount($rangeType);
        $totalSaleReturnCount = $this->service->totalSaleReturnCount($rangeType);
        $totalPurchasesReturnCount = $this->service->totalPurchasesReturnCount($rangeType);

        // ---- Calculations ----
        $revenue = $totalSaleAmount - $totalSaleReturnAmount;
        $paymentReceived = $this->service->totalPaymentReceived($rangeType);
        $paymentSent = $this->service->totalPaymentSent($rangeType);
        $paymentNet = $paymentReceived - $paymentSent;
        $profitLoss = $this->service->calculateProfitLoss($rangeType);

        return [
            // ---- Amounts ----
            'total_sale_amount' => $totalSaleAmount,
            'total_purchases_amount' => $totalPurchasesAmount,
            'total_sale_return_amount' => $totalSaleReturnAmount,
            'total_purchases_return_amount' => $totalPurchasesReturnAmount,

            // ---- Counts ----
            'total_sale_count' => $totalSaleCount,
            'total_purchases_count' => $totalPurchasesCount,
            'total_sale_return_count' => $totalSaleReturnCount,
            'total_purchases_return_count' => $totalPurchasesReturnCount,

            // ---- Financials ----
            'revenue' => $revenue,
            'payment_received' => $paymentReceived,
            'payment_sent' => $paymentSent,
            'payment_net' => $paymentNet,

            // ---- Profit/Loss ----
            'profit_loss' => $profitLoss,

        ];
    }

}

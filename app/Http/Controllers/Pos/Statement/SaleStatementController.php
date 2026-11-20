<?php

namespace App\Http\Controllers\Pos\Statement;

use App\Http\Controllers\Controller;
use App\Models\Pos\Sale\Sale;
use App\Filters\Pos\Report\SalesReportFilter;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Services\Setting\SettingService;
use Illuminate\Pagination\LengthAwarePaginator;

class SaleStatementController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $filter = new SalesReportFilter();

        $baseQuery = Sale::query()
            ->filters($filter)
            ->whereDate('sales.date', Carbon::today())
            ->with([
                'contact:id,name,profile_picture',
                'payments.paymentMethod',
                'items',
                'seller:id,name,profile_photo',
                'tenant:id,name',
            ])
            ->withCount('items as total_items')
            ->withSum('payments', 'amount');

        // Pagination
        $paginated = (clone $baseQuery)->paginate($perPage);
        $currentPage = $paginated->currentPage();

        $paginated = $paginated->through(function ($row, $index) use ($perPage, $currentPage) {
            $received = (float) ($row->payments_sum_amount ?? 0);
            $due = max($row->grand_total - $received, 0);

            return [
                'sl' => (($currentPage - 1) * $perPage) + $index + 1,
                'id' => $row->id,
                'date' => $row->date,
                'full_invoice_number' => $row->full_invoice_number,
                'customer_name' => $row->contact->name ?? '-',
                'customer_image' => $row->contact?->profile_picture,
                'seller_name' => $row->seller?->name ?? '-',
                'seller_image' => $row->seller?->profile_photo,
                'warehouse_name' => $row->tenant?->name ?? '-',
                'status' => $row->status,
                'transaction_mode' => $row->transaction_type,
                'payment' => $row->payments->first(),
                'narration' => $row->payments
                    ->map(fn ($p) => optional($p->paymentMethod)->name)
                    ->filter()
                    ->unique()
                    ->implode(', '),
                'total_items' => (int) $row->total_items,
                'grand_total' => (float) $row->grand_total,
                'discount_amount' => (float) ($row->discount_amount ?? 0),
                'paid_amount' => $received,
                'received_amount' => $received,
                'due_amount' => $due,
            ];
        });

        $grandTotal = (clone $baseQuery)->sum('grand_total');
        $paidAmount = (clone $baseQuery)
            ->join('transactions', function ($join) {
                $join->on('sales.id', '=', 'transactions.model_id')
                    ->where('transactions.model_type', Sale::class);
            })
            ->sum('transactions.amount');

        $totalItems = (clone $baseQuery)
            ->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
            ->sum('sale_items.quantity');

        $totalDue = $grandTotal - $paidAmount;

        return response()->json([
            ...$paginated->toArray(),
            'grand_total' => [
                'total_sales' => (float)$grandTotal,
                'total_returns' => 0,
                'total_items' => (float)$totalItems,
                'paid_amount' => (float)$paidAmount,
                'due_amount' => (float)$totalDue,
                'grand_total' => (float)$grandTotal,
            ],
        ]);
    }

    private const PDF_TOGGLEABLE_COLUMNS = ['transaction_mode', 'narration', 'total_item', 'total_amount', 'due_amount', 'received_amount'];

    public function saleStatementPdf(Request $request)
    {
        set_time_limit(120);
        $isAppAdmin = auth()->user()->isAppAdmin();

        $columns = $request->filled('columns')
            ? array_intersect(explode(',', $request->input('columns')), self::PDF_TOGGLEABLE_COLUMNS)
            : self::PDF_TOGGLEABLE_COLUMNS;

        $filter = new SalesReportFilter();

        $salesQuery = Sale::query()
            ->with(['contact:id,name', 'payments.paymentMethod', 'items'])
            ->filters($filter)
            ->whereDate('date', Carbon::today());

        if (!$isAppAdmin && tenant()) {
            $salesQuery->where('tenant_id', tenant()->id);
        }

        $sales = $salesQuery->get();

        $combined = $sales->map(function($sale) {
            $received = $sale->payments->sum('amount');
            $due = $sale->grand_total - $received;
            return [
                'date_obj' => Carbon::parse($sale->date),
                'id' => $sale->id,
                'date' => Carbon::parse($sale->date)->format('d.m.y'),
                'customer_name' => $sale->contact->name ?? '-',
                'transaction_mode' => 'Sales',
                'narration' => $sale->payments->map(fn($p) => optional($p->paymentMethod)->name)->unique()->implode(', '),
                'invoice' => $sale->full_invoice_number,
                'total_item' => $sale->items->sum('quantity'),
                'total_amount' => (float)$sale->grand_total,
                'due_amount' => (float)$due,
                'received_amount' => (float)$received,
            ];
        })->sortByDesc(function($item) {
            return $item['date_obj']->format('Y-m-d') . $item['id'];
        })->values();

        // Add SL after sorting
        $combined = $combined->map(function($item, $index) {
            $item['sl'] = $index + 1;
            return $item;
        });

        $totalSales = $sales->sum('grand_total');
        $totalItems = $sales->sum(fn($s) => $s->items->sum('quantity'));
        $totalReceived = $sales->sum(fn($s) => $s->payments->sum('amount'));
        $totalDue = $totalSales - $totalReceived;

        $wareHouse = $request->input('warehouse');
        $firstWarehouseId = is_array($wareHouse) ? ($wareHouse[0] ?? null) : (strpos($wareHouse, ',') !== false ? explode(',', $wareHouse)[0] : $wareHouse);
        $tenantId = $isAppAdmin ? $firstWarehouseId : (tenant() ? tenant()->id : null);

        $appSettings = resolve(SettingService::class)->getFormattedSettings('app');
        $tenantSettings = resolve(SettingService::class)->getFormattedSettings('tenant', $tenantId);

        $pdf = Pdf::loadView('pdf.sale-statement', [
            'sales' => $combined,
            'date' => Carbon::now()->format('d-m-y'),
            'time' => Carbon::now()->format('H:i:s'),
            'total_sales' => $totalSales,
            'total_returns' => 0,
            'total_items' => $totalItems,
            'total_received' => $totalReceived,
            'total_due' => $totalDue,
            'shop_info' => $tenantId ? DB::table('tenants')->where('id', $tenantId)->first() : null,
            'tenantSettings' => array_merge($appSettings, $tenantSettings),
            'columns' => $columns,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('sale-statement.pdf');
    }
}

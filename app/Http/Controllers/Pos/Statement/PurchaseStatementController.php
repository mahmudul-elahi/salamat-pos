<?php

namespace App\Http\Controllers\Pos\Statement;

use App\Http\Controllers\Controller;
use App\Models\Pos\Purchase\Purchase;
use App\Filters\Pos\Report\PurchaseReportFilter;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Services\Setting\SettingService;
use Illuminate\Pagination\LengthAwarePaginator;

class PurchaseStatementController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $filter = new PurchaseReportFilter();

        $baseQuery = Purchase::query()
            ->filters($filter)
            ->whereDate('purchases.date', Carbon::today())
            ->with([
                'contact:id,name,profile_picture',
                'payments.paymentMethod',
                'items',
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
                'supplier_name' => $row->contact->name ?? '-',
                'supplier_image' => $row->contact?->profile_picture,
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
        // Total Paid Amount (sum of transactions for purchase)
        $paidAmount = (clone $baseQuery)
            ->join('transactions', function ($join) {
                $join->on('purchases.id', '=', 'transactions.model_id')
                    ->where('transactions.model_type', Purchase::class);
            })
            ->sum('transactions.amount');

        $totalItems = (clone $baseQuery)
            ->join('purchase_items', 'purchases.id', '=', 'purchase_items.purchase_id')
            ->sum('purchase_items.quantity');

        $totalDue = $grandTotal - $paidAmount;

        return response()->json([
            ...$paginated->toArray(),
            'grand_total' => [
                'total_purchase' => (float)$grandTotal,
                'total_returns' => 0,
                'total_items' => (float)$totalItems,
                'paid_amount' => (float)$paidAmount,
                'due_amount' => (float)$totalDue,
                'grand_total' => (float)$grandTotal,
            ],
        ]);
    }

    private const PDF_TOGGLEABLE_COLUMNS = ['transaction_mode', 'narration', 'total_item', 'total_amount', 'due_amount', 'received_amount'];

    public function purchaseStatementPdf(Request $request)
    {
        set_time_limit(120);
        $isAppAdmin = auth()->user()->isAppAdmin();

        $columns = $request->filled('columns')
            ? array_intersect(explode(',', $request->input('columns')), self::PDF_TOGGLEABLE_COLUMNS)
            : self::PDF_TOGGLEABLE_COLUMNS;

        $filter = new PurchaseReportFilter();

        $purchasesQuery = Purchase::query()
            ->with(['contact:id,name', 'payments.paymentMethod', 'items'])
            ->filters($filter)
            ->whereDate('date', Carbon::today());

        if (!$isAppAdmin && tenant()) {
            $purchasesQuery->where('tenant_id', tenant()->id);
        }

        $purchases = $purchasesQuery->get();

        $combined = $purchases->map(function($purchase) {
            $received = $purchase->payments->sum('amount');
            $due = $purchase->grand_total - $received;
            return [
                'date_obj' => Carbon::parse($purchase->date),
                'id' => $purchase->id,
                'date' => Carbon::parse($purchase->date)->format('d.m.y'),
                'supplier_name' => $purchase->contact->name ?? '-',
                'transaction_mode' => 'Purchase',
                'narration' => $purchase->payments->map(fn($p) => optional($p->paymentMethod)->name)->unique()->implode(', '),
                'invoice' => $purchase->full_invoice_number,
                'total_item' => $purchase->items->sum('quantity'),
                'total_amount' => (float)$purchase->grand_total,
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

        $totalPurchases = $purchases->sum('grand_total');
        $totalItems = $purchases->sum(fn($s) => $s->items->sum('quantity'));
        $totalReceived = $purchases->sum(fn($s) => $s->payments->sum('amount'));
        $totalDue = $totalPurchases - $totalReceived;

        $wareHouse = $request->input('warehouse');
        $firstWarehouseId = is_array($wareHouse) ? ($wareHouse[0] ?? null) : (strpos($wareHouse, ',') !== false ? explode(',', $wareHouse)[0] : $wareHouse);
        $tenantId = $isAppAdmin ? $firstWarehouseId : (tenant() ? tenant()->id : null);

        $appSettings = resolve(SettingService::class)->getFormattedSettings('app');
        $tenantSettings = resolve(SettingService::class)->getFormattedSettings('tenant', $tenantId);

        $pdf = Pdf::loadView('pdf.purchase-statement', [
            'purchases' => $combined,
            'date' => Carbon::now()->format('d-m-y'),
            'time' => Carbon::now()->format('H:i:s'),
            'total_purchases' => $totalPurchases,
            'total_returns' => 0,
            'total_items' => $totalItems,
            'total_received' => $totalReceived,
            'total_due' => $totalDue,
            'shop_info' => $tenantId ? DB::table('tenants')->where('id', $tenantId)->first() : null,
            'tenantSettings' => array_merge($appSettings, $tenantSettings),
            'columns' => $columns,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('purchase-statement.pdf');
    }
}

<?php

namespace App\Http\Controllers\Pos\Statement;

use App\Http\Controllers\Controller;
use App\Models\Pos\PurchaseReturn\PurchaseReturn;
use App\Filters\Pos\Report\PurchaseReportFilter;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Services\Setting\SettingService;
use Illuminate\Pagination\LengthAwarePaginator;

class PurchaseReturnStatementController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $baseQuery = PurchaseReturn::query()
            ->filters(new PurchaseReportFilter())
            ->whereDate('purchase_returns.date', Carbon::today())
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
                'invoice_full_number' => $row->full_invoice_number,
                'supplier_name' => $row->contact->name ?? '-',
                'supplier_image' => $row->contact?->profile_picture,
                'warehouse_name' => $row->tenant?->name ?? '-',
                'status' => $row->status,
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
                $join->on('purchase_returns.id', '=', 'transactions.model_id')
                    ->where('transactions.model_type', PurchaseReturn::class);
            })
            ->sum('transactions.amount');

        $totalItems = (clone $baseQuery)
            ->join('purchase_return_items', 'purchase_returns.id', '=', 'purchase_return_items.purchase_return_id')
            ->sum('purchase_return_items.quantity');

        return response()->json([
            ...$paginated->toArray(),
            'grand_total' => [
                'total_items' => $totalItems,
                'grand_total' => $grandTotal,
                'paid_amount' => $paidAmount,
                'due_amount' => ($grandTotal - $paidAmount)
            ],
        ]);
    }


    private const PDF_TOGGLEABLE_COLUMNS = ['transaction_mode', 'narration', 'total_item', 'total_amount', 'due_amount', 'received_amount'];

    public function purchaseReturnStatementPdf(Request $request)
    {
        set_time_limit(120);
        $isAppAdmin = auth()->user()->isAppAdmin();

        $columns = $request->filled('columns')
            ? array_intersect(explode(',', $request->input('columns')), self::PDF_TOGGLEABLE_COLUMNS)
            : self::PDF_TOGGLEABLE_COLUMNS;

        $filter = new PurchaseReportFilter();

        $returnsQuery = PurchaseReturn::query()
            ->with(['contact:id,name', 'payments.paymentMethod', 'items'])
            ->filters($filter)
            ->whereDate('date', Carbon::today());

        if (!$isAppAdmin && tenant()) {
            $returnsQuery->where('tenant_id', tenant()->id);
        }

        $returns = $returnsQuery->get();

        $combined = $returns->map(function ($return) {
            $received = $return->payments->sum('amount');
            $due = $return->grand_total - $received;
            return [
                'date_obj' => Carbon::parse($return->date),
                'id' => $return->id,
                'date' => Carbon::parse($return->date)->format('d.m.y'),
                'supplier_name' => $return->contact->name ?? '-',
                'transaction_mode' => 'Purchase Return',
                'narration' => $return->payments->map(fn($p) => optional($p->paymentMethod)->name)->unique()->implode(', '),
                'invoice' => $return->full_invoice_number,
                'total_item' => $return->items->sum('quantity'),
                'total_amount' => (float)$return->grand_total,
                'due_amount' => (float)$due,
                'received_amount' => (float)$received,
            ];
        })->sortByDesc(function ($item) {
            return $item['date_obj']->format('Y-m-d') . $item['id'];
        })->values();

        // Add SL after sorting
        $combined = $combined->map(function ($item, $index) {
            $item['sl'] = $index + 1;
            return $item;
        });

        $totalReturns = $returns->sum('grand_total');
        $totalItems = $returns->sum(fn($s) => $s->items->sum('quantity'));
        $totalReceived = $returns->sum(fn($s) => $s->payments->sum('amount'));
        $totalDue = $totalReturns - $totalReceived;

        $wareHouse = $request->input('warehouse');
        $firstWarehouseId = is_array($wareHouse) ? ($wareHouse[0] ?? null) : (strpos($wareHouse, ',') !== false ? explode(',', $wareHouse)[0] : $wareHouse);
        $tenantId = $isAppAdmin ? $firstWarehouseId : (tenant() ? tenant()->id : null);

        $appSettings = resolve(SettingService::class)->getFormattedSettings('app');
        $tenantSettings = resolve(SettingService::class)->getFormattedSettings('tenant', $tenantId);

        $pdf = Pdf::loadView('pdf.purchase-return-statement', [
            'purchases' => $combined,
            'date' => Carbon::now()->format('d-m-y'),
            'time' => Carbon::now()->format('H:i:s'),
            'total_purchases' => 0,
            'total_returns' => $totalReturns,
            'total_items' => $totalItems,
            'total_received' => $totalReceived,
            'total_due' => $totalDue,
            'shop_info' => $tenantId ? DB::table('tenants')->where('id', $tenantId)->first() : null,
            'tenantSettings' => array_merge($appSettings, $tenantSettings),
            'columns' => $columns,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('purchase-return-statement.pdf');
    }
}

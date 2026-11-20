<?php

namespace App\Http\Controllers\Pos\Report;

use App\Exceptions\GeneralException;
use App\Filters\Pos\Report\SalesReportFilter;
use App\Http\Controllers\Controller;
use App\Models\Pos\SaleReturn\SaleReturn;
use App\Models\Tenant\Tenant;
use App\Services\Setting\SettingService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class SaleReturnReportController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $salesQuery = SaleReturn::query()
            ->with(['contact:id,name,profile_picture', 'payments.paymentMethod', 'createdBy:id,name', 'tenant:id,name'])
            ->withCount('items as total_items')
            ->filters(new SalesReportFilter('sale_returns'))
            ->orderByDesc('id');

        $paginated = (clone $salesQuery)->paginate($perPage)->through(function ($row) {
            $received = $row->payments->sum('amount');
            $due = $row->grand_total - $received;

            return [
                'id' => $row->id,
                'date' => $row->date,
                'full_invoice_number' => $row->full_invoice_number,
                'customer_name' => $row->contact->name ?? '-',
                'customer_image' => $row->contact?->profile_picture,
                'narration' => $row->payments->map(fn ($p) => optional($p->paymentMethod)->name)->unique()->implode(', '),
                'payment_methods' => $row->payments->map(fn ($p) => $p->paymentMethod)->filter()->unique('id')->values()->map(fn ($pm) => ['name' => $pm->name, 'logo' => $pm->logo])->toArray(),
                'total_item' => $row->total_items,
                'grand_total' => $row->grand_total,
                'received_amount' => $received,
                'due_amount' => $due,
                'created_by_name' => $row->createdBy?->name ?? '-',
                'warehouse_name' => $row->tenant?->name ?? '-',
                'status' => $row->status,
            ];
        });

        $grandTotal = (clone $salesQuery)->sum('grand_total');
        $totalReceivedAmount = (clone $salesQuery)
            ->join('transactions', function ($join) {
                $join->on('sale_returns.id', '=', 'transactions.model_id')
                    ->where('transactions.model_type', SaleReturn::class);
            })
            ->whereColumn('transactions.tenant_id', 'sale_returns.tenant_id')
            ->sum('transactions.amount');

        $totalItems = (clone $salesQuery)
            ->join('sale_return_items', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
            ->sum('sale_return_items.quantity');

        $totalDue = $grandTotal - $totalReceivedAmount;

        return response()->json([
            ...$paginated->toArray(),
            'grand_total' => [
                'total_item' => $totalItems,
                'received_amount' => $totalReceivedAmount,
                'due_amount' => $totalDue,
                'grand_total' => $grandTotal,
            ],
        ]);
    }

    public function saleReturnReportPdf(Request $request)
    {
        $isAppAdmin = auth()->user()->isAppAdmin();
        $wareHouse = $request->input('warehouse');

        if ($isAppAdmin && ! $wareHouse) {
            throw new GeneralException('Select warehouse first');
        }

        $tenantId = $isAppAdmin ? $wareHouse : tenant()->id;

        $salesQuery = SaleReturn::query()
            ->filters(new SalesReportFilter)
            ->when($isAppAdmin, function ($query) use ($tenantId) {
                $query->where('sale_returns.tenant_id', $tenantId);
            })
            ->with(['contact:id,name', 'payments.paymentMethod', 'items'])
            ->withCount('items as total_items')
            ->orderByDesc('id');

        $sales = (clone $salesQuery)->get();

        $returns = $sales->map(function ($sale) {
            $received = $sale->payments->sum('amount');
            $due = $sale->grand_total - $received;

            return [
                'id' => $sale->id,
                'date' => $sale->date,
                'customer_name' => $sale->contact->name ?? '-',
                'narration' => $sale->payments->map(fn ($p) => optional($p->paymentMethod)->name)->unique()->implode(', '),
                'full_invoice_number' => $sale->full_invoice_number,
                'total_item' => $sale->total_items,
                'grand_total' => $sale->grand_total,
                'due_amount' => $due,
                'received_amount' => $received,
            ];
        });

        $grandTotal = (clone $salesQuery)->sum('grand_total');

        $totalReceived = (clone $salesQuery)
            ->join('transactions', function ($join) {
                $join->on('sale_returns.id', '=', 'transactions.model_id')
                    ->where('transactions.model_type', SaleReturn::class);
            })
            ->sum('transactions.amount');

        $totalDueAmount = $grandTotal - $totalReceived;

        $totalItems = (clone $salesQuery)
            ->join('sale_return_items', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
            ->sum('sale_return_items.quantity');

        $tenantSettings = resolve(SettingService::class)->getFormattedSettings('tenant', $tenantId);

        $shopInfo = Tenant::query()->find($tenantId);

        $pdf = Pdf::loadView('pdf.sale-return-report', [
            'returns' => $returns,
            'total_items' => $totalItems,
            'grand_total' => $grandTotal,
            'total_received' => $totalReceived,
            'total_due' => $totalDueAmount,
            'warehouse_name' => optional($shopInfo)->name,
            'tenantSettings' => $tenantSettings,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('sale-return-report.pdf');
    }
}

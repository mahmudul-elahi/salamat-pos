<?php

namespace App\Http\Controllers\Pos\Report;

use App\Filters\Pos\Report\CashTransferReportFilter;
use App\Http\Controllers\Controller;
use App\Models\Pos\CashTransfer\CashTransfer;
use App\Services\Setting\SettingService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class CashTransferReportController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $transfersQuery = CashTransfer::query()
            ->filters(new CashTransferReportFilter)
            ->with(['fromTenant:id,name', 'toTenant:id,name', 'createdBy:id,name', 'approvedBy:id,name'])
            ->orderByDesc('id');

        $paginated = (clone $transfersQuery)->paginate($perPage)->through(fn ($row) => [
            'id' => $row->id,
            'reference_no' => $row->reference_no,
            'transfer_date' => $row->transfer_date,
            'from_warehouse_name' => $row->fromTenant?->name ?? '-',
            'to_warehouse_name' => $row->toTenant?->name ?? '-',
            'amount' => $row->amount,
            'status' => $row->status,
            'created_by_name' => $row->createdBy?->name ?? '-',
            'received_by_name' => $row->approvedBy?->name ?? '-',
        ]);

        $totalTransfers = (clone $transfersQuery)->count();
        $totalAmount = (clone $transfersQuery)->sum('amount');

        return response()->json([
            ...$paginated->toArray(),
            'grand_total' => [
                'total_transfers' => $totalTransfers,
                'amount' => $totalAmount,
            ],
        ]);
    }

    public function pdf(Request $request)
    {
        $isAppAdmin = auth()->user()->isAppAdmin();
        $warehouse = $request->input('warehouse') ?? $request->input('from_warehouse');
        $tenantId = $isAppAdmin ? $warehouse : tenant()->id;

        $transfersQuery = CashTransfer::query()
            ->filters(new CashTransferReportFilter)
            ->with(['fromTenant:id,name', 'toTenant:id,name', 'createdBy:id,name', 'approvedBy:id,name'])
            ->orderByDesc('id');

        $transfers = (clone $transfersQuery)->get()->map(fn ($row) => [
            'reference_no' => $row->reference_no,
            'transfer_date' => $row->transfer_date,
            'from_warehouse_name' => $row->fromTenant?->name ?? '-',
            'to_warehouse_name' => $row->toTenant?->name ?? '-',
            'amount' => $row->amount,
            'status' => $row->status,
            'created_by_name' => $row->createdBy?->name ?? '-',
            'received_by_name' => $row->approvedBy?->name ?? '-',
        ]);

        $totalTransfers = (clone $transfersQuery)->count();
        $totalAmount = (clone $transfersQuery)->sum('amount');

        $tenantSettings = resolve(SettingService::class)->getFormattedSettings('tenant', $tenantId);

        $pdf = Pdf::loadView('pdf.cash-transfer-report', [
            'transfers' => $transfers,
            'total_transfers' => $totalTransfers,
            'total_amount' => $totalAmount,
            'tenantSettings' => $tenantSettings,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('cash-transfer-report.pdf');
    }
}

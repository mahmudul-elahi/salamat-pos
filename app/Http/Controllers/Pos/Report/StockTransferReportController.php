<?php

namespace App\Http\Controllers\Pos\Report;

use App\Filters\Pos\Report\StockTransferReportFilter;
use App\Http\Controllers\Controller;
use App\Models\Pos\StockTransfer\StockTransfer;
use App\Services\Setting\SettingService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class StockTransferReportController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $transfersQuery = StockTransfer::query()
            ->filters(new StockTransferReportFilter())
            ->with(['fromTenant:id,name', 'toTenant:id,name', 'createdBy:id,name', 'approvedBy:id,name'])
            ->withCount('items as total_items')
            ->orderByDesc('id');

        $paginated = (clone $transfersQuery)->paginate($perPage)->through(function ($row) {
            return [
                'id' => $row->id,
                'reference_no' => $row->reference_no,
                'transfer_date' => $row->transfer_date,
                'from_warehouse_name' => $row->fromTenant?->name ?? '-',
                'to_warehouse_name' => $row->toTenant?->name ?? '-',
                'total_items' => $row->total_items,
                'status' => $row->status,
                'created_by_name' => $row->createdBy?->name ?? '-',
                'received_by_name' => $row->approvedBy?->name ?? '-',
            ];
        });

        $totalTransfers = (clone $transfersQuery)->count();
        $totalItems = (clone $transfersQuery)
            ->join('stock_transfer_items', 'stock_transfers.id', '=', 'stock_transfer_items.stock_transfer_id')
            ->count('stock_transfer_items.id');

        return response()->json([
            ...$paginated->toArray(),
            'grand_total' => [
                'total_transfers' => $totalTransfers,
                'total_items' => $totalItems,
            ],
        ]);
    }

    public function pdf(Request $request)
    {
        $isAppAdmin = auth()->user()->isAppAdmin();
        $warehouse = $request->input('warehouse') ?? $request->input('from_warehouse');
        $tenantId = $isAppAdmin ? $warehouse : tenant()->id;

        $transfersQuery = StockTransfer::query()
            ->filters(new StockTransferReportFilter())
            ->with(['fromTenant:id,name', 'toTenant:id,name', 'createdBy:id,name', 'approvedBy:id,name'])
            ->withCount('items as total_items')
            ->orderByDesc('id');

        $transfers = (clone $transfersQuery)->get()->map(function ($row) {
            return [
                'reference_no' => $row->reference_no,
                'transfer_date' => $row->transfer_date,
                'from_warehouse_name' => $row->fromTenant?->name ?? '-',
                'to_warehouse_name' => $row->toTenant?->name ?? '-',
                'total_items' => $row->total_items,
                'status' => $row->status,
                'created_by_name' => $row->createdBy?->name ?? '-',
                'received_by_name' => $row->approvedBy?->name ?? '-',
            ];
        });

        $totalTransfers = (clone $transfersQuery)->count();
        $totalItems = (clone $transfersQuery)
            ->join('stock_transfer_items', 'stock_transfers.id', '=', 'stock_transfer_items.stock_transfer_id')
            ->count('stock_transfer_items.id');

        $tenantSettings = resolve(SettingService::class)->getFormattedSettings('tenant', $tenantId);

        $pdf = Pdf::loadView('pdf.stock-transfer-report', [
            'transfers' => $transfers,
            'total_transfers' => $totalTransfers,
            'total_items' => $totalItems,
            'tenantSettings' => $tenantSettings,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('stock-transfer-report.pdf');
    }
}

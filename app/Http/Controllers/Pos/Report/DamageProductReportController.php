<?php

namespace App\Http\Controllers\Pos\Report;

use App\Filters\Pos\Report\DamageProductReportFilter;
use App\Http\Controllers\Controller;
use App\Models\Pos\DamageProduct\DamageProduct;
use App\Services\Setting\SettingService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class DamageProductReportController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $damageQuery = DamageProduct::query()
            ->filters(new DamageProductReportFilter)
            ->with(['tenant:id,name', 'createdBy:id,name', 'approvedBy:id,name'])
            ->withCount('items as total_items')
            ->orderByDesc('id');

        $paginated = (clone $damageQuery)->paginate($perPage)->through(fn ($row) => [
            'id' => $row->id,
            'reference_no' => $row->reference_no,
            'damage_date' => $row->damage_date,
            'warehouse_name' => $row->tenant?->name ?? '-',
            'total_items' => $row->total_items,
            'status' => $row->status,
            'created_by_name' => $row->createdBy?->name ?? '-',
            'approved_by_name' => $row->approvedBy?->name ?? '-',
        ]);

        $totalRecords = (clone $damageQuery)->count();
        $totalItems = (clone $damageQuery)
            ->join('damage_product_items', 'damage_products.id', '=', 'damage_product_items.damage_product_id')
            ->count('damage_product_items.id');

        return response()->json([
            ...$paginated->toArray(),
            'grand_total' => [
                'total_records' => $totalRecords,
                'total_items' => $totalItems,
            ],
        ]);
    }

    public function pdf(Request $request)
    {
        $isAppAdmin = auth()->user()->isAppAdmin();
        $warehouse = $request->input('warehouse');
        $tenantId = $isAppAdmin ? $warehouse : tenant()->id;

        $damageQuery = DamageProduct::query()
            ->filters(new DamageProductReportFilter)
            ->with(['tenant:id,name', 'createdBy:id,name', 'approvedBy:id,name'])
            ->withCount('items as total_items')
            ->orderByDesc('id');

        $records = (clone $damageQuery)->get()->map(fn ($row) => [
            'reference_no' => $row->reference_no,
            'damage_date' => $row->damage_date,
            'warehouse_name' => $row->tenant?->name ?? '-',
            'total_items' => $row->total_items,
            'status' => $row->status,
            'created_by_name' => $row->createdBy?->name ?? '-',
            'approved_by_name' => $row->approvedBy?->name ?? '-',
        ]);

        $totalRecords = (clone $damageQuery)->count();
        $totalItems = (clone $damageQuery)
            ->join('damage_product_items', 'damage_products.id', '=', 'damage_product_items.damage_product_id')
            ->count('damage_product_items.id');

        $tenantSettings = resolve(SettingService::class)->getFormattedSettings('tenant', $tenantId);

        $pdf = Pdf::loadView('pdf.damage-product-report', [
            'records' => $records,
            'total_records' => $totalRecords,
            'total_items' => $totalItems,
            'tenantSettings' => $tenantSettings,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('damage-product-report.pdf');
    }
}

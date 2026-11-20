<?php

namespace App\Http\Controllers\Pos\Statement;

use App\Filters\Pos\Report\DepositReportFilter;
use App\Http\Controllers\Controller;
use App\Models\Pos\Investment\Investment;
use App\Services\Setting\SettingService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DepositStatementController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $filter = new DepositReportFilter;

        $grandTotal = Investment::query()
            ->filters($filter)
            ->sum('amount');

        $baseQuery = Investment::query()
            ->filters($filter)
            ->whereDate('investments.date', Carbon::today())
            ->with([
                'user:id,name,profile_photo',
                'createdBy:id,name',
                'investmentCategory:id,name',
                'contact:id,name,profile_picture',
                'tenant:id,name',
            ]);

        // Pagination
        $paginated = (clone $baseQuery)->paginate($perPage);
        $currentPage = $paginated->currentPage();

        $paginated = $paginated->through(fn ($row, $index) => [
            'sl' => (($currentPage - 1) * $perPage) + $index + 1,
            'id' => $row->id,
            'full_invoice_number' => 'INV-'.$row->id,
            'date' => $row->date,
            'title' => $row->title,
            'investment_category' => $row->investmentCategory,
            'contact' => $row->contact,
            'user' => $row->user,
            'created_by_name' => $row->createdBy?->name ?? '-',
            'warehouse_name' => $row->tenant?->name ?? '-',
            'transaction_mode' => 'Deposit',
            'amount' => (float) $row->amount,
            'note' => $row->note ?? '-',
        ]);

        return response()->json([
            ...$paginated->toArray(),
            'grand_total' => [
                'total_items' => (int) $paginated->total(),
                'amount' => $grandTotal,
            ],
        ]);
    }

    private const array PDF_TOGGLEABLE_COLUMNS = ['user', 'narration', 'amount'];

    public function depositStatementPdf(Request $request)
    {
        $isAppAdmin = auth()->user()->isAppAdmin();

        $columns = $request->filled('columns')
            ? array_intersect(explode(',', $request->input('columns')), self::PDF_TOGGLEABLE_COLUMNS)
            : self::PDF_TOGGLEABLE_COLUMNS;

        $depositQuery = Investment::query()
            ->with(['user:id,name'])
            ->filters(new DepositReportFilter)
            ->whereDate('investments.date', Carbon::today());

        if (! $isAppAdmin && tenant()) {
            $depositQuery->where('tenant_id', tenant()->id);
        }

        $deposits = $depositQuery->get();

        $combined = $deposits->map(fn ($deposit) => [
            'date_obj' => Carbon::parse($deposit->date),
            'id' => $deposit->id,
            'date' => Carbon::parse($deposit->date)->format('d.m.y'),
            'title' => $deposit->title,
            'user' => $deposit->user?->name ?? '-',
            'transaction_mode' => 'Deposit',
            'narration' => $deposit->payments->map(fn ($p) => optional($p->paymentMethod)->name)->unique()->implode(', '),
            'amount' => (float) $deposit->amount,
            'note' => $deposit->note ?? '-',
        ])->sortByDesc(fn ($item) => $item['date_obj']->format('Y-m-d').$item['id'])->values();

        // Add SL after sorting
        $combined = $combined->map(function ($item, $index) {
            $item['sl'] = $index + 1;

            return $item;
        });

        $totalDeposit = $deposits->sum('amount');

        $wareHouse = $request->input('warehouse');
        $firstWarehouseId = is_array($wareHouse) ? ($wareHouse[0] ?? null) : (str_contains($wareHouse, ',') ? explode(',', $wareHouse)[0] : $wareHouse);
        $tenantId = $isAppAdmin ? $firstWarehouseId : (tenant() ? tenant()->id : null);

        $appSettings = resolve(SettingService::class)->getFormattedSettings('app');
        $tenantSettings = resolve(SettingService::class)->getFormattedSettings('tenant', $tenantId);

        $pdf = Pdf::loadView('pdf.deposit-statement', [
            'deposits' => $combined,
            'date' => Carbon::now()->format('d-m-y'),
            'time' => Carbon::now()->format('H:i:s'),
            'total_deposit' => $totalDeposit,
            'shop_info' => $tenantId ? DB::table('tenants')->where('id', $tenantId)->first() : null,
            'tenantSettings' => array_merge($appSettings, $tenantSettings),
            'columns' => $columns,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('deposit-statement.pdf');
    }
}

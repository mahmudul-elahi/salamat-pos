<?php

namespace App\Http\Controllers\Pos\Statement;

use App\Filters\Pos\Report\IncomeFilter;
use App\Http\Controllers\Controller;
use App\Models\Pos\Income\Income;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Services\Setting\SettingService;

class WithdrawalStatementController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $baseQuery = Income::query()
            ->filters(new IncomeFilter())
            ->whereDate('incomes.date', Carbon::today())
            ->with([
                'user:id,name,profile_photo',
                'createdBy:id,name',
                'category:id,name',
                'contact:id,name,profile_picture',
                'tenant:id,name',
                'payments.paymentMethod'
            ])
            ->withSum('payments', 'amount');

        // Pagination
        $paginated = (clone $baseQuery)->paginate($perPage);
        $currentPage = $paginated->currentPage();

        $paginated = $paginated->through(function ($row, $index) use ($perPage, $currentPage) {

            return [
                'sl' => (($currentPage - 1) * $perPage) + $index + 1,
                'id' => $row->id,
                'full_invoice_number' => 'WTH-' . $row->id,
                'date' => $row->date,
                'title' => $row->title,
                'category' => $row->category,
                'contact' => $row->contact,
                'user' => $row->user,
                'created_by_name' => $row->createdBy?->name ?? '-',
                'warehouse_name' => $row->tenant?->name ?? '-',
                'narration' => $row->payments
                    ->map(fn($p) => optional($p->paymentMethod)->name)
                    ->filter()
                    ->unique()
                    ->implode(', '),
                'amount' => (float)$row->amount,
                'note' => $row->note ?? '-',
            ];
        });

        $grandTotal = (clone $baseQuery)->sum('amount');

        return response()->json([
            ...$paginated->toArray(),
            'grand_total' => [
                'total_items' => (int)$paginated->total(),
                'amount' => (float)$grandTotal,
            ],
        ]);
    }

    private const PDF_TOGGLEABLE_COLUMNS = ['user', 'narration', 'amount'];

    public function withdrawalStatementPdf(Request $request)
    {
        $isAppAdmin = auth()->user()->isAppAdmin();

        $columns = $request->filled('columns')
            ? array_intersect(explode(',', $request->input('columns')), self::PDF_TOGGLEABLE_COLUMNS)
            : self::PDF_TOGGLEABLE_COLUMNS;

        $withdrawalQuery = Income::query()
            ->with(['user:id,name'])
            ->filters(new IncomeFilter())
            ->whereDate('incomes.date', Carbon::today());

        if (!$isAppAdmin && tenant()) {
            $withdrawalQuery->where('tenant_id', tenant()->id);
        }

        $withdrawals = $withdrawalQuery->get();

        $combined = $withdrawals->map(function ($withdrawal) {
            return [
                'date_obj' => Carbon::parse($withdrawal->date),
                'id' => $withdrawal->id,
                'date' => Carbon::parse($withdrawal->date)->format('d.m.y'),
                'title' => $withdrawal->title,
                'user' => $withdrawal->user?->name ?? '-',
                'transaction_mode' => 'Withdrawal',
                'narration' => $withdrawal->payments->map(fn($p) => optional($p->paymentMethod)->name)->unique()->implode(', '),
                'amount' => (float)$withdrawal->amount,
                'note' => $withdrawal->note ?? '-',
            ];
        })->sortByDesc(function ($item) {
            return $item['date_obj']->format('Y-m-d') . $item['id'];
        })->values();

        // Add SL after sorting
        $combined = $combined->map(function ($item, $index) {
            $item['sl'] = $index + 1;
            return $item;
        });

        $totalWithdrawal = $withdrawals->sum('amount');

        $wareHouse = $request->input('warehouse');
        $firstWarehouseId = is_array($wareHouse) ? ($wareHouse[0] ?? null) : (strpos($wareHouse, ',') !== false ? explode(',', $wareHouse)[0] : $wareHouse);
        $tenantId = $isAppAdmin ? $firstWarehouseId : (tenant() ? tenant()->id : null);

        $appSettings = resolve(SettingService::class)->getFormattedSettings('app');
        $tenantSettings = resolve(SettingService::class)->getFormattedSettings('tenant', $tenantId);

        $pdf = Pdf::loadView('pdf.withdrawal-statement', [
            'withdrawals' => $combined,
            'date' => Carbon::now()->format('d-m-y'),
            'time' => Carbon::now()->format('H:i:s'),
            'total_withdrawal' => $totalWithdrawal,
            'shop_info' => $tenantId ? DB::table('tenants')->where('id', $tenantId)->first() : null,
            'tenantSettings' => array_merge($appSettings, $tenantSettings),
            'columns' => $columns,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('withdrawal-statement.pdf');
    }
}

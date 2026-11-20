<?php

namespace App\Http\Controllers\Pos\Statement;

use App\Filters\Landlord\Expense\ExpenseFilter;
use App\Http\Controllers\Controller;
use App\Models\Pos\Expense\Expense;
use App\Services\Setting\SettingService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExpenseStatementController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $filter = new ExpenseFilter;

        $baseQuery = Expense::query()
            ->filters($filter)
            ->whereDate('expenses.date', Carbon::today())
            ->with(['category:id,name', 'payments.paymentMethod', 'createdBy:id,name', 'tenant:id,name'])
            ->withSum('payments', 'amount');

        $paginated = (clone $baseQuery)->paginate($perPage);
        $currentPage = $paginated->currentPage();

        $paginated = $paginated->through(function ($row, $index) use ($perPage, $currentPage) {
            $received = (float) ($row->payments_sum_amount ?? 0);
            $due = max($row->amount - $received, 0);
            $paymentMethod = $row->payments->first()?->paymentMethod;

            return [
                'sl' => (($currentPage - 1) * $perPage) + $index + 1,
                'id' => $row->id,
                'full_invoice_number' => 'EXP-'.$row->id,
                'date' => $row->date,
                'title' => $row->title,
                'category' => $row->category,
                'note' => $row->note,
                'payment_method' => $paymentMethod ? ['name' => $paymentMethod->name, 'logo' => $paymentMethod->logo] : null,
                'warehouse_name' => $row->tenant?->name ?? '-',
                'created_by_name' => $row->createdBy?->name ?? '-',
                'amount' => (float) $row->amount,
                'grand_total' => (float) $row->amount,
                'paid_amount' => $received,
                'due_amount' => $due,
            ];
        });

        $totalExpenses = (clone $baseQuery)->sum('amount');
        $paidAmount = (clone $baseQuery)
            ->join('transactions', function ($join) {
                $join->on('expenses.id', '=', 'transactions.model_id')
                    ->where('transactions.model_type', Expense::class);
            })
            ->sum('transactions.amount');

        return response()->json([
            ...$paginated->toArray(),
            'grand_total' => [
                'total_items' => (int) $paginated->total(),
                'grand_total' => (float) $totalExpenses,
                'paid_amount' => (float) $paidAmount,
                'due_amount' => (float) ($totalExpenses - $paidAmount),
            ],
        ]);
    }

    private const array PDF_TOGGLEABLE_COLUMNS = ['category', 'payment_method', 'amount', 'paid_amount', 'due_amount'];

    public function expenseStatementPdf(Request $request)
    {
        set_time_limit(120);
        $isAppAdmin = auth()->user()->isAppAdmin();

        $columns = $request->filled('columns')
            ? array_intersect(explode(',', $request->input('columns')), self::PDF_TOGGLEABLE_COLUMNS)
            : self::PDF_TOGGLEABLE_COLUMNS;

        $filter = new ExpenseFilter;

        $query = Expense::query()
            ->with(['category', 'payments.paymentMethod'])
            ->filters($filter);

        if (! $isAppAdmin && tenant()) {
            $query->where('tenant_id', tenant()->id);
        }

        $expenses = $query->get()->map(function ($expense, $index) {
            $received = (float) $expense->payments->sum('amount');

            return [
                'sl' => $index + 1,
                'date' => Carbon::parse($expense->date)->format('d.m.y'),
                'title' => $expense->title,
                'category' => $expense->category->name ?? '-',
                'payment_method' => $expense->payments->first()?->paymentMethod->name ?? '-',
                'amount' => (float) $expense->amount,
                'paid_amount' => $received,
            ];
        });

        $totalAmount = $expenses->sum('amount');
        $totalPaid = $expenses->sum('paid_amount');
        $totalDue = $totalAmount - $totalPaid;

        $wareHouse = $request->input('warehouse');
        $firstWarehouseId = is_array($wareHouse) ? ($wareHouse[0] ?? null) : (str_contains($wareHouse, ',') ? explode(',', $wareHouse)[0] : $wareHouse);
        $tenantId = $isAppAdmin ? $firstWarehouseId : (tenant() ? tenant()->id : null);

        $appSettings = resolve(SettingService::class)->getFormattedSettings('app');
        $tenantSettings = resolve(SettingService::class)->getFormattedSettings('tenant', $tenantId);

        $pdf = Pdf::loadView('pdf.expense-statement', [
            'expenses' => $expenses,
            'date' => Carbon::now()->format('d-m-y'),
            'time' => Carbon::now()->format('H:i:s'),
            'total_expenses' => $totalAmount,
            'total_paid' => $totalPaid,
            'total_due' => $totalDue,
            'shop_info' => $tenantId ? DB::table('tenants')->where('id', $tenantId)->first() : null,
            'tenantSettings' => array_merge($appSettings, $tenantSettings),
            'columns' => $columns,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('expense-statement.pdf');
    }
}

<?php

namespace App\Http\Controllers\Pos\Report;

use App\Filters\Landlord\Expense\ExpenseFilter;
use App\Http\Controllers\Controller;
use App\Jobs\Report\GenerateExpenseReportPdfJob;
use App\Models\Pos\Expense\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ExpenseReportController extends Controller
{
    public function __construct(protected ExpenseFilter $filter)
    {
    }

    public function index()
    {
        $perPage = request('per_page', 10);


        $queryData = Expense::query()
            ->filters($this->filter)
            ->with(['category:id,name', 'paymentMethod:id,name,logo', 'createdBy:id,name', 'tenant:id,name'])
            ->orderByDesc('id');

        $paginated = (clone $queryData)
            ->paginate($perPage)
            ->through(fn($expense) => [
                ...$expense->toArray(),
                'full_invoice_number' => 'EXP-' . $expense->id,
            ]);

        $totalSum = (clone $queryData)
            ->sum('amount');

        return response()->json([
            ...$paginated->toArray(),
            'grand_total' => [
                'amount' => $totalSum,
            ],
        ]);
    }

    /**
     * An expense report can span thousands of rows, so — mirroring
     * stockReportPdf() — this only queues GenerateExpenseReportPdfJob and
     * hands back a token. The frontend polls expenseReportPdfStatus() until
     * the job's cache entry says completed/failed, then downloads the file
     * directly from its public disk URL. Requires a queue worker to be
     * running (php artisan queue:work) — QUEUE_CONNECTION=database in this app.
     */
    public function expenseReportPdf(Request $request)
    {
        $warehouse = auth()->user()->isAppAdmin() ? $request->input('shop') : tenant()->id;

        $token = (string) Str::uuid();

        Cache::put(GenerateExpenseReportPdfJob::cacheKey($token), ['status' => 'queued'], now()->addHour());

        GenerateExpenseReportPdfJob::dispatch($token, $warehouse, [
            'category' => $request->input('category'),
            'date' => $request->input('date'),
            'payment_method' => $request->input('payment_method'),
            'created_by' => $request->input('created_by'),
        ])->onQueue('report-generate');

        return response()->json(['token' => $token]);
    }

    public function expenseReportPdfStatus(string $token)
    {
        $status = Cache::get(GenerateExpenseReportPdfJob::cacheKey($token));

        if (!$status) {
            return response()->json(['status' => 'not_found'], 404);
        }

        return response()->json($status);
    }

    /**
     * Called by the frontend right after it triggers the browser download,
     * so the generated PDF doesn't sit in storage forever — the token's
     * path is deterministic (see GenerateExpenseReportPdfJob::generate()),
     * so no lookup is needed beyond confirming the cache entry exists.
     */
    public function expenseReportPdfCleanup(string $token)
    {
        if (Cache::has(GenerateExpenseReportPdfJob::cacheKey($token))) {
            Storage::disk('public')->delete("expense-reports/{$token}.pdf");
            Cache::forget(GenerateExpenseReportPdfJob::cacheKey($token));
        }

        return response()->json(['deleted' => true]);
    }

    private function getTodayDataQuery($date, $warehouse)
    {

    }


}

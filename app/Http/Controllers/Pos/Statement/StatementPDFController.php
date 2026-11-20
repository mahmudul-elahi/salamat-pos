<?php

namespace App\Http\Controllers\Pos\Statement;

use Illuminate\Http\Request;

use App\Http\Controllers\Controller;

use App\Jobs\Report\GenerateStockReportPdfJob;
use App\Jobs\Report\GenerateStockStatementPdfJob;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;


class StatementPDFController extends Controller
{

    /**
     * A stock statement PDF can span thousands of product/variant rows, so
     * — mirroring stockReportPdf() below — this only queues
     * GenerateStockStatementPdfJob and hands back a token. The frontend
     * polls stockStatementPdfStatus() until the job's cache entry says
     * completed/failed, then downloads the file directly from its public
     * disk URL. Requires a queue worker to be running
     * (php artisan queue:work) — QUEUE_CONNECTION=database in this app.
     */
    public function stockStatementPdf(Request $request)
    {
        $wareHouse  = $request->input('warehouse');
        $isAppAdmin = auth()->user()->isAppAdmin();
        $tenantId   = $isAppAdmin ? $wareHouse : tenant()->id;

        $token = (string) Str::uuid();

        Cache::put(GenerateStockStatementPdfJob::cacheKey($token), ['status' => 'queued'], now()->addHour());

        $columns = $request->input('columns');

        GenerateStockStatementPdfJob::dispatch($token, $tenantId, [
            'brand'    => $request->input('brand'),
            'category' => $request->input('category'),
            'product'  => $request->input('product'),
            'supplier' => $request->input('supplier'),
            'date'     => $request->input('date'),
            'columns'  => $columns ? explode(',', $columns) : [],
        ])->onQueue('report-generate');

        return response()->json(['token' => $token]);
    }

    public function stockStatementPdfStatus(string $token)
    {
        $status = Cache::get(GenerateStockStatementPdfJob::cacheKey($token));

        if (!$status) {
            return response()->json(['status' => 'not_found'], 404);
        }

        return response()->json($status);
    }

    /**
     * Called by the frontend right after it triggers the browser download,
     * so the generated PDF doesn't sit in storage forever — the token's
     * path is deterministic (see GenerateStockStatementPdfJob::generate()),
     * so no lookup is needed beyond confirming the cache entry exists.
     */
    public function stockStatementPdfCleanup(string $token)
    {
        if (Cache::has(GenerateStockStatementPdfJob::cacheKey($token))) {
            Storage::disk('public')->delete("stock-statements/{$token}.pdf");
            Cache::forget(GenerateStockStatementPdfJob::cacheKey($token));
        }

        return response()->json(['deleted' => true]);
    }

    public function stockReportPdf(Request $request)
    {
        $wareHouse  = $request->input('warehouse');
        $isAppAdmin = auth()->user()->isAppAdmin();
        $tenantId   = $isAppAdmin ? $wareHouse : tenant()->id;

        $token = (string) Str::uuid();

        Cache::put(GenerateStockReportPdfJob::cacheKey($token), ['status' => 'queued'], now()->addHour());

        $columns = $request->input('columns');

        GenerateStockReportPdfJob::dispatch($token, $tenantId, [
            'brand'    => $request->input('brand'),
            'category' => $request->input('category'),
            'product'  => $request->input('product'),
            'supplier' => $request->input('supplier'),
            'date'     => $request->input('date'),
            'columns'  => $columns ? explode(',', $columns) : [],
        ])->onQueue('report-generate');

        return response()->json(['token' => $token]);
    }

    public function stockReportPdfStatus(string $token)
    {
        $status = Cache::get(GenerateStockReportPdfJob::cacheKey($token));

        if (!$status) {
            return response()->json(['status' => 'not_found'], 404);
        }

        return response()->json($status);
    }

    /**
     * Called by the frontend right after it triggers the browser download,
     * so the generated PDF doesn't sit in storage forever — the token's
     * path is deterministic (see GenerateStockReportPdfJob::generate()),
     * so no lookup is needed beyond confirming the cache entry exists.
     */
    public function stockReportPdfCleanup(string $token)
    {
        if (Cache::has(GenerateStockReportPdfJob::cacheKey($token))) {
            Storage::disk('public')->delete("stock-reports/{$token}.pdf");
            Cache::forget(GenerateStockReportPdfJob::cacheKey($token));
        }

        return response()->json(['deleted' => true]);
    }


    public function transactionStatementPdf(Request $request)
    {

        //        $pdf = PDF::loadView('pdf.transaction-statement', [
        //            'transactions' => $transactions,
        //        ])->setPaper('a4', 'portrait');
        //
        //        return $pdf->stream('transaction-statement.pdf');
    }
}

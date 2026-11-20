<?php

namespace App\Jobs\Report;

use App\Exceptions\GeneralException;
use App\Models\Pos\Expense\Expense;
use App\Services\Setting\SettingService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * An expense report PDF can span thousands of rows, so — mirroring
 * GenerateProfitLossPdfJob — it's built off the request/response cycle: the
 * controller dispatches this job and hands back a token, the frontend polls
 * expense-report-pdf-status/{token} until this job's cache entry flips to
 * completed/failed, then downloads the file directly from its public disk URL.
 */
class GenerateExpenseReportPdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;

    public int $tries = 1;

    public function __construct(
        public string $token,
        public ?int $tenantId,
        public array $filters,
    ) {
        // The production Supervisor worker only listens on
        // hrm-employee-invitation,hrm-leave-assign,report-generate — not
        // the default queue. Without this, the job silently sits pending
        // forever there and the frontend just times out.
        $this->onQueue('report-generate');
    }

    public static function cacheKey(string $token): string
    {
        return "expense-report-pdf:{$token}";
    }

    /**
     * Expense rows are cheap to render (no images/serials), so this can
     * afford a much higher cap than the stock report's.
     */
    private const int MAX_ROWS = 5000;

    public function handle(): void
    {
        Cache::put(self::cacheKey($this->token), ['status' => 'processing'], now()->addHour());

        $path = $this->generate();

        Cache::put(self::cacheKey($this->token), [
            'status' => 'completed',
            'url' => Storage::disk('public')->url($path),
        ], now()->addHour());
    }

    public function failed(Throwable $exception): void
    {
        Cache::put(self::cacheKey($this->token), [
            'status' => 'failed',
            'message' => $exception->getMessage(),
        ], now()->addHour());
    }

    private function generate(): string
    {
        $tenantId = $this->tenantId;
        $categoryIds = ! empty($this->filters['category']) ? explode(',', $this->filters['category']) : null;
        $paymentMethodIds = ! empty($this->filters['payment_method']) ? explode(',', $this->filters['payment_method']) : null;
        $createdByIds = ! empty($this->filters['created_by']) ? explode(',', $this->filters['created_by']) : null;
        $dateRange = $this->filters['date'] ?? null;

        // Mirrors ExpenseFilter's own methods, kept as plain local variables +
        // ->when() (like GenerateProfitLossPdfJob) rather than reused as a
        // Filter object, since Filter::apply() reads off the global request()
        // helper, which a queued job shouldn't rely on.
        $baseQuery = Expense::query()
            ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->when($categoryIds, function ($q) use ($categoryIds) {
                $q->whereHas('category', fn ($q) => $q->whereIn('expense_category_id', $categoryIds));
            })
            ->when($paymentMethodIds, fn ($q) => $q->whereIn('payment_method_id', $paymentMethodIds))
            ->when($createdByIds, fn ($q) => $q->whereIn('created_by', $createdByIds))
            ->when($dateRange, function ($q) use ($dateRange) {
                $range = is_string($dateRange) ? json_decode($dateRange, true) : $dateRange;
                if (is_array($range) && count($range) >= 2) {
                    $start = Carbon::parse($range[0])->startOfDay();
                    $end = Carbon::parse($range[1])->endOfDay();
                    $q->whereBetween('date', [$start, $end]);
                }
            });

        $rowCount = (clone $baseQuery)->count();

        if ($rowCount === 0) {
            throw new GeneralException('No expense data found');
        }

        if ($rowCount > self::MAX_ROWS) {
            throw new GeneralException(
                "This report has {$rowCount} rows, which is too large to export as a PDF at once. "
                    .'Please narrow it down with a filter and try again.'
            );
        }

        $transactions = $baseQuery
            ->with(['category:id,name', 'tenant:id,name'])
            ->orderByDesc('id')
            ->get();

        $tenantSettings = resolve(SettingService::class)->getFormattedSettings('tenant', $tenantId);
        $shopInfo = $tenantId ? DB::table('tenants')->where('id', $tenantId)->first() : null;

        $pdf = Pdf::loadView('pdf.expense-report', [
            'transactions' => $transactions,
            'shopInfo' => $shopInfo,
            'tenantSettings' => $tenantSettings,
        ])->setPaper('a4', 'portrait');

        $path = "expense-reports/{$this->token}.pdf";
        Storage::disk('public')->put($path, $pdf->output());

        return $path;
    }
}

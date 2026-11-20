<?php

namespace App\Jobs\Report;

use App\Models\Pos\Income\Income;
use App\Models\Pos\Investment\Investment;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Throwable;

class GenerateDirectorProfilePdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;

    public int $tries = 1;

    public function __construct(
        public string $token,
        public int $directorId,
        public ?int $tenantId = null,
    ) {
        $this->onQueue('report-generate');
    }

    public static function cacheKey(string $token): string
    {
        return "director-profile-pdf:{$token}";
    }

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
        $director = User::query()->with('tenants')->findOrFail($this->directorId);

        $deposits = Investment::query()
            ->where('user_id', $director->id)
            ->when($this->tenantId, fn ($q) => $q->where('tenant_id', $this->tenantId))
            ->with('tenant')
            ->latest()
            ->get();

        $withdrawals = Income::query()
            ->where('user_id', $director->id)
            ->when($this->tenantId, fn ($q) => $q->where('tenant_id', $this->tenantId))
            ->with('tenant')
            ->latest()
            ->get();

        $totalDeposit = (float) $deposits->sum('amount');
        $totalWithdraw = (float) $withdrawals->sum('amount');

        $shops = $this->tenantId
            ? $director->tenants->where('id', $this->tenantId)->pluck('name', 'id')
            : $director->tenants->pluck('name', 'id');
        $shopIds = $shops->keys();

        $shopDeposits = Investment::query()
            ->where('user_id', $director->id)
            ->whereIn('tenant_id', $shopIds)
            ->groupBy('tenant_id')
            ->selectRaw('tenant_id, SUM(amount) as total')
            ->pluck('total', 'tenant_id');

        $shopWithdraws = Income::query()
            ->where('user_id', $director->id)
            ->whereIn('tenant_id', $shopIds)
            ->groupBy('tenant_id')
            ->selectRaw('tenant_id, SUM(amount) as total')
            ->pluck('total', 'tenant_id');

        $shopBalances = $shops->map(function ($name, $shopId) use ($shopDeposits, $shopWithdraws) {
            $deposit = (float) ($shopDeposits[$shopId] ?? 0);
            $withdraw = (float) ($shopWithdraws[$shopId] ?? 0);

            return [
                'shop_name' => $name,
                'total_deposit' => $deposit,
                'total_withdraw' => $withdraw,
                'balance' => $deposit - $withdraw,
            ];
        })->values();

        $pdf = Pdf::loadView('pdf.director-profile', [
            'logo' => config('settings.application.company_logo'),
            'companyName' => config('settings.application.company_name'),
            'director' => $director,
            'reportTitle' => $this->tenantId ? 'Director Shop Profile' : 'Director Profile',
            'shopName' => $this->tenantId ? $shops->first() : null,
            'totalDeposit' => $totalDeposit,
            'totalWithdraw' => $totalWithdraw,
            'totalBalance' => $totalDeposit - $totalWithdraw,
            'deposits' => $deposits,
            'withdrawals' => $withdrawals,
            'shopBalances' => $shopBalances,
        ])
            ->setPaper('a4', 'portrait')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', false);

        $path = "director-profile-reports/{$this->token}.pdf";
        Storage::disk('public')->put($path, $pdf->output());

        return $path;
    }
}

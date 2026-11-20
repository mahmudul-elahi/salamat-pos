<?php

namespace App\Services\Courier;

use App\Models\Pos\Courier\CourierOrder;
use Illuminate\Support\Str;

class FraudCheckService
{
    /**
     * Statuses that count as a genuinely completed delivery.
     */
    private const SUCCESS_STATUSES = ['delivered', 'partially delivered'];

    /**
     * Statuses that count against the customer (refused/failed/returned parcel).
     */
    private const CANCEL_STATUSES = ['cancelled', 'return', 'pickup failed', 'pickup cancel'];

    /**
     * Check a customer's own delivery/return history across every courier
     * we've integrated (Pathao, Steadfast, ...), using our own order data
     * instead of a paid third-party lookup.
     */
    public function checkByPhone(string $phone): array
    {
        $normalizedPhone = $this->lastDigits($phone);

        $orders = CourierOrder::query()
            ->whereRaw("RIGHT(REGEXP_REPLACE(recipient_phone, '[^0-9]', ''), 10) = ?", [$normalizedPhone])
            ->get(['provider', 'status', 'amount_to_collect', 'courier_created_at', 'created_at']);

        $overall = $this->summarize($orders);

        $perProvider = [];
        foreach ($orders->groupBy('provider') as $provider => $providerOrders) {
            $perProvider[$provider] = $this->summarize($providerOrders);
        }

        return [
            'risk_level' => $this->riskLevel($overall),
            'total_orders' => $overall['total'],
            'total_success' => $overall['success'],
            'total_cancel' => $overall['cancel'],
            'success_ratio' => $overall['ratio'],
            ...$perProvider,
        ];
    }

    /**
     * Local DB-based stats for a single provider, in the same shape the
     * azmolla/fraud-checker-bd-courier package returns. Used as a fallback
     * when that package's live courier login fails.
     */
    public function statsByProvider(string $phone, string $provider): array
    {
        $normalizedPhone = $this->lastDigits($phone);

        $orders = CourierOrder::query()
            ->where('provider', $provider)
            ->whereRaw("RIGHT(REGEXP_REPLACE(recipient_phone, '[^0-9]', ''), 10) = ?", [$normalizedPhone])
            ->get(['status']);

        $summary = $this->summarize($orders);

        return [
            'success' => $summary['success'],
            'cancel' => $summary['cancel'],
            'total' => $summary['total'],
            'success_ratio' => $summary['ratio'] ?? 0,
            'source' => 'local',
        ];
    }

    private function summarize($orders): array
    {
        $total = $orders->count();
        $success = $orders->filter(fn($o) => in_array(strtolower($o->status ?? ''), self::SUCCESS_STATUSES))->count();
        $cancel = $orders->filter(fn($o) => in_array(strtolower($o->status ?? ''), self::CANCEL_STATUSES))->count();

        return [
            'total_parcel' => $total,
            'total' => $total,
            'success_parcel' => $success,
            'success' => $success,
            'cancelled_parcel' => $cancel,
            'cancel' => $cancel,
            'success_ratio' => $total > 0 ? round(($success / $total) * 100, 1) : null,
            'ratio' => $total > 0 ? round(($success / $total) * 100, 1) : null,
        ];
    }

    private function riskLevel(array $summary): string
    {
        if ($summary['total'] === 0) {
            return 'safe';
        }

        $cancelRatio = $summary['cancel'] / $summary['total'];

        if ($cancelRatio >= 0.5) {
            return 'danger';
        }

        if ($cancelRatio >= 0.2) {
            return 'warning';
        }

        return 'safe';
    }

    private function lastDigits(string $phone): string
    {
        return Str::of($phone)->replaceMatches('/[^0-9]/', '')->substr(-10);
    }
}

<?php

namespace App\Http\Controllers\Pos\Courier;

use App\Http\Controllers\Controller;
use App\Models\Pos\Courier\FraudCheckRecord;
use App\Services\Courier\BdCourierApiService;
use App\Services\Courier\FraudCheckService;
use Azmolla\FraudCheckerBdCourier\Services\CarrybeeService;
use Azmolla\FraudCheckerBdCourier\Services\PaperflyService;
use Azmolla\FraudCheckerBdCourier\Services\PathaoService;
use Azmolla\FraudCheckerBdCourier\Services\RedxService;
use Azmolla\FraudCheckerBdCourier\Services\SteadfastService;
use Illuminate\Http\Request;

class BdCourierController extends Controller
{
    /**
     * Display metadata for every courier we report on, keyed the same way
     * bdcourier.com's own aggregator API keys its response — so both
     * endpoints return interchangeable shapes for the frontend.
     */
    private const array COURIER_META = [
        'pathao' => ['name' => 'Pathao', 'logo' => '/assets/images/couriers/pathao-logo.png'],
        'steadfast' => ['name' => 'SteadFast', 'logo' => '/assets/images/couriers/steadfast-logo.png'],
        'redx' => ['name' => 'Redx', 'logo' => '/assets/images/couriers/redx-logo.png'],
        'paperfly' => ['name' => 'PaperFly', 'logo' => '/assets/images/couriers/paperfly-logo.png'],
        'carrybee' => ['name' => 'CarryBee', 'logo' => '/assets/images/couriers/carrybee-logo.webp'],
        'parceldex' => ['name' => 'ParcelDex', 'logo' => '/assets/images/couriers/parceldex-logo.png'],
    ];

    /**
     * Couriers we can log into live via the azmolla package. ParcelDex has
     * no such integration, so it only ever falls back to our own order
     * history.
     */
    private const array LIVE_SERVICES = [
        'pathao' => PathaoService::class,
        'steadfast' => SteadfastService::class,
        'redx' => RedxService::class,
        'paperfly' => PaperflyService::class,
        'carrybee' => CarrybeeService::class,
    ];

    public function __construct(
        protected FraudCheckService $fraudCheckService,
        protected BdCourierApiService $bdCourierApiService,
    ) {}

    public function checkFraud(Request $request)
    {
        $data = $request->validate([
            'phone' => ['required', 'string', 'max:20'],
        ]);

        $phone = $data['phone'];

        $couriers = [];
        $totalSuccess = 0;
        $totalCancel = 0;
        $totalParcel = 0;

        foreach (self::COURIER_META as $provider => $meta) {
            $liveServiceClass = self::LIVE_SERVICES[$provider] ?? null;

            $stats = $this->statsFor($phone, $provider, $liveServiceClass
                ? fn () => (new $liveServiceClass)->getDeliveryStats($phone)
                : fn () => ['error' => 'Not integrated']);

            $couriers[$provider] = [
                'logo' => asset($meta['logo']),
                'name' => $meta['name'],
                'total_parcel' => (int) $stats['total'],
                'success_ratio' => (float) $stats['success_ratio'],
                'success_parcel' => (int) $stats['success'],
                'cancelled_parcel' => (int) $stats['cancel'],
                'provider' => $stats['source'],
            ];

            $totalSuccess += (int) $stats['success'];
            $totalCancel += (int) $stats['cancel'];
            $totalParcel += (int) $stats['total'];
        }

        return response()->json([
            ...$couriers,
            'reports' => [],
            'summary' => [
                'total_parcel' => $totalParcel,
                'success_ratio' => $totalParcel > 0 ? round($totalSuccess / $totalParcel * 100, 2) : 0,
                'success_parcel' => $totalSuccess,
                'cancelled_parcel' => $totalCancel,
            ],
        ]);
    }

    /**
     * Same phone, checked against bdcourier.com's own hosted aggregator API
     * instead of our live courier-portal logins. Kept as a separate check
     * (and a separate button) since it's a paid, rate-limited third-party
     * call and also surfaces merchant-reported fraud reports that our own
     * per-courier checks don't have.
     */
    public function checkFraudViaBdCourierApi(Request $request)
    {
        $data = $request->validate([
            'phone' => ['required', 'string', 'max:20'],
        ]);

        $phone = $data['phone'];

        $existing = FraudCheckRecord::query()
            ->where('phone', $phone)
            ->where('provider', 'bdcourier_api')
            ->whereDate('checked_at', now()->toDateString())
            ->first();

        if ($existing) {
            return response()->json($existing->raw_response);
        }

        $payload = $this->bdCourierApiService->checkFraud($phone);
        $summary = $payload['summary'] ?? [];

        FraudCheckRecord::query()->updateOrCreate(
            ['phone' => $phone, 'provider' => 'bdcourier_api'],
            [
                'success' => $summary['success_parcel'] ?? 0,
                'cancel' => $summary['cancelled_parcel'] ?? 0,
                'total' => $summary['total_parcel'] ?? 0,
                'success_ratio' => $summary['success_ratio'] ?? 0,
                'source' => 'live',
                'raw_response' => $payload,
                'checked_at' => now(),
            ]
        );

        return response()->json($payload);
    }

    /**
     * A live courier login is only attempted once per phone/provider per
     * calendar day (checking the same customer repeatedly used to trip
     * Steadfast's own login rate-limiter). Every other check that day reuses
     * the last saved result from fraud_check_records.
     */
    private function statsFor(string $phone, string $provider, callable $liveLookup): array
    {
        $existing = FraudCheckRecord::query()
            ->where('phone', $phone)
            ->where('provider', $provider)
            ->whereDate('checked_at', now()->toDateString())
            ->first();

        if ($existing) {
            return [
                'success' => $existing->success,
                'cancel' => $existing->cancel,
                'total' => $existing->total,
                'success_ratio' => (float) $existing->success_ratio,
                'source' => $existing->source,
                'checked_at' => $existing->checked_at->toDateTimeString(),
            ];
        }

        try {
            $liveStats = $liveLookup();
        } catch (\Throwable $e) {
            // Missing credentials (unconfigured courier) or any other live
            // lookup failure — fall back to our own order history below.
            $liveStats = ['error' => $e->getMessage()];
        }

        $source = 'live';

        if (isset($liveStats['error'])) {
            $liveStats = $this->fraudCheckService->statsByProvider($phone, $provider);
            $source = 'local';
        }

        $record = FraudCheckRecord::query()->updateOrCreate(
            ['phone' => $phone, 'provider' => $provider],
            [
                'success' => $liveStats['success'] ?? 0,
                'cancel' => $liveStats['cancel'] ?? 0,
                'total' => $liveStats['total'] ?? 0,
                'success_ratio' => $liveStats['success_ratio'] ?? 0,
                'source' => $source,
                'raw_response' => $liveStats,
                'checked_at' => now(),
            ]
        );

        return [
            'success' => $record->success,
            'cancel' => $record->cancel,
            'total' => $record->total,
            'success_ratio' => (float) $record->success_ratio,
            'source' => $record->source,
            'checked_at' => $record->checked_at->toDateTimeString(),
        ];
    }
}

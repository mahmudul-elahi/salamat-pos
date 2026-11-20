<?php

namespace App\Services\Courier;

use App\Exceptions\GeneralException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class SteadfastService
{
    private function client()
    {
        return Http::timeout(20)
            ->withHeaders([
                'Api-Key' => config('services.steadfast.api_key'),
                'Secret-Key' => config('services.steadfast.secret_key'),
            ])
            ->acceptJson();
    }

    /**
     * @throws GeneralException
     */
    public function createOrder(array $payload): array
    {
        $response = $this->client()->post(config('services.steadfast.base_url') . '/create_order', $payload);

        if (!$response->successful()) {
            $message = $response->json('message') ?? 'Failed to create Steadfast order';
            throw new GeneralException($message, 422);
        }

        return $response->json();
    }

    /**
     * @throws GeneralException
     */
    public function getStatusByConsignmentId(string $consignmentId): array
    {
        $response = $this->client()->get(config('services.steadfast.base_url') . "/status_by_cid/{$consignmentId}");

        if (!$response->successful()) {
            throw new GeneralException($response->json('message') ?? 'Failed to fetch order status', 422);
        }

        return $response->json();
    }

    /**
     * Steadfast keys status on either handle, so callers that only kept the
     * tracking code aren't stuck.
     *
     * @throws GeneralException
     */
    public function getStatusByTrackingCode(string $trackingCode): array
    {
        $response = $this->client()->get(config('services.steadfast.base_url') . "/status_by_trackingcode/{$trackingCode}");

        if (!$response->successful()) {
            throw new GeneralException($response->json('message') ?? 'Failed to fetch order status', 422);
        }

        return $response->json();
    }

    /**
     * Steadfast's public tracking page.
     *
     * The URL cannot be built from the tracking code: /tl/ takes a separate
     * per-parcel token that Steadfast mints itself (…/tl/mP8ICvFULrVMmRGu…,
     * not …/tl/SFR260807ST5D1851DBD, which 404s). They hand it back as
     * `tracking_link` when the consignment is created, so read it off the
     * stored response rather than guessing.
     *
     * Returns null for orders that arrived without one — e.g. rows imported
     * from their Excel export, which carries no link column.
     */
    public function trackingUrlFrom(?array $rawResponse): ?string
    {
        return $rawResponse['consignment']['tracking_link'] ?? null;
    }

    /**
     * @throws GeneralException
     */
    public function getBalance(): array
    {
        $response = $this->client()->get(config('services.steadfast.base_url') . '/get_balance');

        if (!$response->successful()) {
            throw new GeneralException($response->json('message') ?? 'Failed to fetch balance', 422);
        }

        return $response->json();
    }

    /**
     * District -> Thana (police station) hierarchy. Cached since this data
     * barely ever changes and there's no reason to hit Steadfast on every
     * dropdown open.
     *
     * @throws GeneralException
     */
    public function getPoliceStations(): array
    {
        return Cache::remember('steadfast_police_stations', now()->addDay(), function () {
            $response = $this->client()->get(config('services.steadfast.base_url') . '/police_stations');

            if (!$response->successful()) {
                throw new GeneralException($response->json('message') ?? 'Failed to fetch districts', 422);
            }

            return $response->json('data') ?? [];
        });
    }
}

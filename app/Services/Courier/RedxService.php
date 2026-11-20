<?php

namespace App\Services\Courier;

use App\Exceptions\GeneralException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * REDX courier (v1.0.0-beta). Unlike Steadfast's key/secret pair, REDX
 * authenticates with a single long-lived access token sent as
 * `API-ACCESS-TOKEN: Bearer <token>`.
 */
class RedxService
{
    private function client()
    {
        return Http::timeout(20)
            ->withHeaders([
                'API-ACCESS-TOKEN' => 'Bearer '.config('services.redx.access_token'),
            ])
            ->acceptJson();
    }

    private function url(string $path): string
    {
        return rtrim(config('services.redx.base_url'), '/').'/'.ltrim($path, '/');
    }

    /**
     * @throws GeneralException
     */
    private function handle($response, string $fallbackMessage): array
    {
        if (! $response->successful()) {
            throw new GeneralException($response->json('message') ?? $fallbackMessage, 422);
        }

        return $response->json() ?? [];
    }

    /**
     * Delivery areas, which double as REDX's district/zone list. Cached because
     * this barely changes and there's no reason to hit REDX on every dropdown.
     *
     * @throws GeneralException
     */
    public function getAreas(): array
    {
        return Cache::remember('redx_areas', now()->addDay(), function () {
            $body = $this->handle($this->client()->get($this->url('areas')), 'Failed to fetch REDX areas');

            return $body['areas'] ?? [];
        });
    }

    /**
     * @throws GeneralException
     */
    public function getPickupStores(): array
    {
        $body = $this->handle($this->client()->get($this->url('pickup/stores')), 'Failed to fetch REDX pickup stores');

        return $body['pickup_stores'] ?? [];
    }

    /**
     * Creates a parcel. REDX answers 201 with just `{"tracking_id": "..."}` —
     * that id is the only handle for tracking afterwards, so callers must store
     * it.
     *
     * @throws GeneralException
     */
    public function createParcel(array $payload): array
    {
        return $this->handle(
            $this->client()->post($this->url('parcel'), $payload),
            'Failed to create REDX parcel'
        );
    }

    /**
     * Tracking history, oldest event first.
     *
     * @throws GeneralException
     */
    public function trackParcel(string $trackingId): array
    {
        $body = $this->handle(
            $this->client()->get($this->url("parcel/track/{$trackingId}")),
            'Failed to track REDX parcel'
        );

        return $body['tracking'] ?? [];
    }

    /**
     * Public tracking page for a parcel — what the Track column links to.
     */
    public function trackingUrl(string $trackingId): string
    {
        return 'https://redx.com.bd/track-parcel/?trackingId='.$trackingId;
    }
}

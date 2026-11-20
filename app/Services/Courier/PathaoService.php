<?php

namespace App\Services\Courier;

use App\Exceptions\GeneralException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PathaoService
{
    private const string TOKEN_CACHE_KEY = 'pathao_access_token';

    private function baseUrl(): string
    {
        return rtrim(config('services.pathao.base_url'), '/');
    }

    /**
     * @throws GeneralException
     */
    private function getAccessToken(): string
    {
        return Cache::remember(self::TOKEN_CACHE_KEY, 3600, function () {
            $config = config('services.pathao');

            if (! $config['client_id'] || ! $config['client_secret'] || ! $config['username'] || ! $config['password']) {
                throw new GeneralException('Pathao credentials are not configured', 422);
            }

            $response = Http::timeout(20)
                ->acceptJson()
                ->post($this->baseUrl().'/aladdin/api/v1/issue-token', [
                    'client_id' => $config['client_id'],
                    'client_secret' => $config['client_secret'],
                    'username' => $config['username'],
                    'password' => $config['password'],
                    'grant_type' => $config['grant_type'],
                ]);

            if (! $response->successful() || ! $response->json('access_token')) {
                throw new GeneralException($response->json('message') ?? 'Failed to authenticate with Pathao', 422);
            }

            return $response->json('access_token');
        });
    }

    /**
     * @throws GeneralException
     */
    private function request(string $method, string $uri, array $payload = [], bool $retried = false): array
    {
        $token = $this->getAccessToken();

        $response = Http::timeout(20)
            ->acceptJson()
            ->withToken($token)
            ->{$method}($this->baseUrl().$uri, $payload);

        if ($response->status() === 401 && ! $retried) {
            Cache::forget(self::TOKEN_CACHE_KEY);

            return $this->request($method, $uri, $payload, true);
        }

        if (! $response->successful()) {
            $message = $response->json('message') ?? 'Pathao request failed';

            // Pathao's validation errors come back as {"errors": {"field": ["msg"]}}
            // (a keyed object, not a numeric array) — flatten it onto the
            // generic top-level message so the actual invalid field shows up
            // instead of just "Please fix the given errors".
            $errors = $response->json('errors');
            if (is_array($errors) && $errors !== []) {
                $message .= ': '.collect($errors)->flatten()->implode(' ');
            }

            Log::error('Pathao API error', [
                'uri' => $uri,
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            throw new GeneralException($message, 422);
        }

        return $response->json();
    }

    /**
     * @throws GeneralException
     */
    public function getStores(): array
    {
        return $this->request('get', '/aladdin/api/v1/stores')['data']['data'] ?? [];
    }

    /**
     * @throws GeneralException
     */
    public function getCities(): array
    {
        return $this->request('get', '/aladdin/api/v1/city-list')['data']['data'] ?? [];
    }

    /**
     * @throws GeneralException
     */
    public function getZones(int $cityId): array
    {
        return $this->request('get', "/aladdin/api/v1/cities/{$cityId}/zone-list")['data']['data'] ?? [];
    }

    /**
     * @throws GeneralException
     */
    public function getAreas(int $zoneId): array
    {
        return $this->request('get', "/aladdin/api/v1/zones/{$zoneId}/area-list")['data']['data'] ?? [];
    }

    /**
     * @throws GeneralException
     */
    public function createOrder(array $payload): array
    {
        return $this->request('post', '/aladdin/api/v1/orders', $payload);
    }

    /**
     * Current state of one consignment. Pathao exposes no event history on
     * this endpoint — just the latest status — so the timeline the UI shows is
     * built from the webhook events we've stored plus this as the live check.
     *
     * @throws GeneralException
     */
    public function getOrderInfo(string $consignmentId): array
    {
        return $this->request('get', "/aladdin/api/v1/orders/{$consignmentId}/info")['data'] ?? [];
    }

    /**
     * Pathao's public tracking page — what the Track link opens.
     */
    public function trackingUrl(string $consignmentId): string
    {
        return 'https://merchant.pathao.com/tracking?consignment_id='.$consignmentId;
    }

    /**
     * @throws GeneralException
     */
    public function getOrdersPage(int $storeId, int $page = 1): array
    {
        $token = $this->getAccessToken();

        $response = Http::timeout(30)
            ->acceptJson()
            ->withToken($token)
            ->get($this->baseUrl().'/aladdin/api/v1/orders/all', [
                'store_id' => $storeId,
                'page' => $page,
            ]);

        if (! $response->successful()) {
            $message = $response->json('message') ?? 'Failed to fetch Pathao orders';
            throw new GeneralException($message, 422);
        }

        return $response->json('data') ?? [];
    }
}

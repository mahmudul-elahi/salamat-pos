<?php

namespace App\Services\Courier;

use App\Exceptions\GeneralException;
use Illuminate\Support\Facades\Http;

class BdCourierApiService
{
    private function baseUrl(): string
    {
        return rtrim(config('services.bdcourier.base_url'), '/');
    }

    /**
     * Hits bdcourier.com's hosted aggregator endpoint, which checks a phone
     * number across multiple couriers (Pathao, Steadfast, ParcelDex, Redx,
     * Paperfly, CarryBee) in one call and also returns any merchant-reported
     * fraud reports for that number.
     *
     * @throws GeneralException
     */
    public function checkFraud(string $phone): array
    {
        $apiKey = config('services.bdcourier.api_key');

        if (!$apiKey) {
            throw new GeneralException('BD Courier API key is not configured', 422);
        }

        $response = Http::timeout(20)
            ->acceptJson()
            ->withToken($apiKey)
            ->post($this->baseUrl() . '/courier-check', [
                'phone' => $phone,
            ]);

        if (!$response->successful() || $response->json('status') !== 'success') {
            $message = $response->json('message') ?? 'Failed to check fraud status with BD Courier';
            throw new GeneralException($message, 422);
        }

        return [
            ...($response->json('data') ?? []),
            'reports' => $response->json('reports') ?? [],
        ];
    }
}

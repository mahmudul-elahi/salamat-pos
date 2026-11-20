<?php

namespace App\Helpers\Traits;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Notifies the Next.js frontend to revalidate a page's ISR cache whenever
 * the underlying record (brand, category, slider, ...) changes.
 */
trait RevalidatesFrontend
{
    public function revalidateFrontendPage(string $type): void
    {
        $this->sendRevalidationRequest(['type' => $type]);
    }

    public function revalidateFrontend(string $type, ?string $slug = null): void
    {
        $this->sendRevalidationRequest([
            'type' => $type,
            'slug' => $slug,
        ]);
    }

    protected function sendRevalidationRequest(array $payload): void
    {
        $url = config('app.frontend_url') . '/api/revalidate';

        try {
            $response = Http::timeout(5)
                ->retry(2, 200)
                ->post($url, array_merge($payload, [
                    'secret' => config('app.revalidation_secret'),
                ]));

            if ($response->failed()) {
                Log::warning('Revalidation failed', [
                    'payload' => $payload,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Revalidation request error', [
                'payload' => $payload,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

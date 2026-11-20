<?php

namespace App\Helpers\Traits;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

trait RevalidatesFrontendTrait
{
    public static function bootRevalidatesFrontendTrait(): void
    {
        static::saved(fn($model) => $model->revalidateFrontendCache());
        static::deleted(fn($model) => $model->revalidateFrontendCache());
    }

    public function revalidateFrontendCache(): void
    {
        $type = $this->revalidationType ?? strtolower(class_basename($this));

        // Models without a "slug" column (e.g. Widget, keyed by "type"
        // instead) can point this at whichever attribute identifies the
        // page to revalidate: protected string $revalidationSlugField = 'type';
        //
        // Dot notation (e.g. 'product.slug') reaches through a relation for
        // models like PreOwned that have no slug of their own and use their
        // related Product's slug instead.
        $slugField = $this->revalidationSlugField ?? 'slug';

        if (str_contains($slugField, '.')) {
            $slug = data_get($this, $slugField);

            if ($slug) {
                $this->sendRevalidation([
                    'type' => $type,
                    'slug' => strtolower($slug),
                ]);

                return;
            }
        } elseif (array_key_exists($slugField, $this->getAttributes()) || $this->getOriginal($slugField)) {
            $slugs = array_unique(array_filter([
                $this->getOriginal($slugField),
                $this->{$slugField},
            ]));

            foreach ($slugs as $slug) {
                $this->sendRevalidation([
                    'type' => $type,
                    'slug' => strtolower($slug),
                ]);
            }

            return;
        }

        $this->sendRevalidation([
            'type' => $type,
        ]);
    }

    protected function sendRevalidation(array $payload): void
    {
        try {
            $response = Http::timeout(5)
                ->retry(2, 200)
                ->post(
                    config('app.frontend_url') . '/api/revalidate',
                    array_merge($payload, [
                        'secret' => config('app.revalidation_secret'),
                    ])
                );

            if ($response->failed()) {
                Log::warning('Revalidation failed', [
                    'payload' => $payload,
                    'body' => $response->body(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Revalidation error', [
                'payload' => $payload,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

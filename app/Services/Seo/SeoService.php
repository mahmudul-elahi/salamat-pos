<?php

namespace App\Services\Seo;

use App\Services\BaseService;
use Illuminate\Http\Request;

class SeoService extends BaseService
{
    public function createSeo(Request $request): static
    {
        $this->model->refresh();
        $seos = $request->input('seos', []);

        foreach ($seos as $seoData) {
            $tenantId = $seoData['tenant_id'] ?? null;

            if ($tenantId === 'null' || $tenantId === '') {
                $tenantId = null;
            }

            // Prepare title and description with fallbacks
            $title = $seoData['meta_title'] ?? null;
            $description = $seoData['meta_desc'] ?? null;
            $tags = $seoData['tags'] ?? null;

            // Skip if both title and description are empty or null
            if (empty(trim($title)) && empty(trim($description))) {
                continue;
            }

            // Prepare where clause
            if ($tenantId === null) {
                // Match records where tenant_id IS NULL
                $seo = $this->model->seos()->whereNull('tenant_id')->first();
            } else {
                $seo = $this->model->seos()->where('tenant_id', $tenantId)->first();
            }

            if ($seo) {
                // Update existing
                $seo->update([
                    'tenant_id' => $tenantId,
                    'title' => $title,
                    'description' => $description,
                    'tags' => $seoData['tags'] ?? null,
                ]);
            } else {
                // Create new record
                $this->model->seos()->create([
                    'tenant_id' => $tenantId,
                    'title' => $title,
                    'description' => $description,
                    'tags' => $seoData['tags'] ?? null,
                ]);
            }
        }

        return $this;
    }

    public function updateSeo(Request $request): static
    {
        $this->model->refresh();

        $seos = $request->input('seos', []);

        // Collect all SEO IDs sent from frontend (if any)
        $incomingIds = collect($seos)
            ->pluck('id')
            ->filter() // remove null/empty
            ->toArray();

        // Delete SEO entries NOT present in incoming IDs
        $this->model->seos()
            ->when(count($incomingIds), function ($query) use ($incomingIds) {
                $query->whereNotIn('id', $incomingIds);
            }, function ($query) {
                // If no incoming SEO data, delete all
                $query->whereNotNull('id');
            })
            ->delete();

        foreach ($seos as $seoData) {
            $tenantId = $seoData['tenant_id'] ?? null;

            // Normalize tenant_id: convert 'null' or empty string to actual null
            if ($tenantId === 'null' || $tenantId === '') {
                $tenantId = null;
            }

            $title = $seoData['meta_title'] ?? $seoData['title'] ?? null; // support both keys
            $description = $seoData['meta_desc'] ?? $seoData['description'] ?? null;
            $tags = $seoData['tags'] ?? null;

            // Skip if both title and description are empty or null
            if (empty(trim($title)) && empty(trim($description))) {
                continue;
            }

            if (! empty($seoData['id'])) {
                // Update existing SEO by id
                $seo = $this->model->seos()->find($seoData['id']);
                if ($seo) {
                    $seo->update([
                        'tenant_id' => $tenantId,
                        'title' => $title,
                        'description' => $description,
                        'tags' => $tags,
                    ]);

                    continue;
                }
            }

            // If no id or SEO not found, create new SEO record
            $this->model->seos()->create([
                'tenant_id' => $tenantId,
                'title' => $title,
                'description' => $description,
                'tags' => $tags,
            ]);
        }

        return $this;
    }
}

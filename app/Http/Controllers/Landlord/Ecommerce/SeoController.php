<?php

namespace App\Http\Controllers\Landlord\Ecommerce;

use App\Exceptions\GeneralException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Landlord\Ecommerce\SeoUpdateRequest;
use App\Models\Landlord\Brand\Brand;
use App\Models\Landlord\Category\Category;
use App\Models\Pos\Product\Product;
use App\Models\Pos\Seo\Seo;
use App\Models\Tenant\Tenant;

class SeoController extends Controller
{
    // New Method for Product
    public function productSeoUpdate(SeoUpdateRequest $request, $id)
    {
        return $this->updateSeoLogic($request, 'product', $id);
    }

    // New Method for Category
    public function categorySeoUpdate(SeoUpdateRequest $request, $id)
    {
        return $this->updateSeoLogic($request, 'category', $id);
    }

    // New Method for Brand
    public function brandSeoUpdate(SeoUpdateRequest $request, $id)
    {
        return $this->updateSeoLogic($request, 'brand', $id);
    }

    // Shared private logic to avoid duplication
    private function updateSeoLogic(SeoUpdateRequest $request, $model, $id)
    {
        $modelClass = $this->getModelClass($model);

        if (! $modelClass) {
            throw new GeneralException('Invalid model type');
        }

        $entity = $modelClass::find($id);

        if (! $entity) {
            throw new GeneralException('Resource not found');
        }

        $tenantId = $this->ecommerceTenantId();

        if ($model === 'product') {
            $entity->update([
                'product_name' => $request->title,
                'slug' => $request->slug,
            ]);
        } elseif ($model === 'category') {
            $entity->update([
                'name' => $request->title,
                'title' => $request->title,
                'slug' => $request->slug,
            ]);
        } elseif ($model === 'brand') {
            $entity->update([
                'name' => $request->title,
                'slug' => $request->slug,
            ]);
        }

        // Always save/update the branch-specific description
        $entity->longDescriptions()->updateOrCreate(
            ['tenant_id' => $tenantId],
            ['description' => $request->description]
        );

        $tags = $request->tags;
        if (is_array($tags)) {
            $tags = implode(',', $tags);
        }

        $seo = $entity->seos()->updateOrCreate(
            [
                'tenant_id' => $tenantId,
            ],
            [
                'tenant_id' => $tenantId,
                'title' => $request->meta_title, // Map Meta Title to title column
                'description' => $request->meta_description, // Map Meta Description
                'tags' => $tags,
                'model_type' => $modelClass,
                'model_id' => $id,
            ]
        );

        return successResponse('SEO data updated successfully', $seo);
    }

    // Keep show but maybe split it later if needed? User mainly asked for 3 update methods.
    // I will keep show as is but generic for now, or user might want show split too?
    // "seo controller a 3 ta method thakbe 1 productSeoUpdate, CategorySeoUpdate, BrandSeoUpdate"
    // The user primarily focused on "Update". I'll keep show generic or I can alias routes to show.
    public function show($model, $id)
    {
        $modelClass = $this->getModelClass($model);

        if (! $modelClass) {
            throw new GeneralException('Invalid model type');
        }

        $entity = $modelClass::query()->find($id);

        if (! $entity) {
            throw new GeneralException('Resource not found');
        }

        // Determine title/name field based on model
        $title = $entity->name ?? $entity->product_name ?? '';

        $slug = $entity->slug ?? '';

        $tenantId = $this->ecommerceTenantId();

        $seo = $entity->seos()->where('tenant_id', $tenantId)->first();

        // Fetch description using the accessor (which handles fallback)
        $description = $entity->getLongDescriptionAttribute($tenantId);

        return response()->json([
            'title' => $title,
            'slug' => $slug,
            'description' => $description ?? '',
            'meta_title' => $seo?->title ?? '',
            'meta_description' => $seo?->description ?? '',
            'tags' => $seo?->tags ?? '',
        ]);
    }

    /**
     * SEO managed here belongs to the TEKZO e-commerce site, so the tenant is
     * resolved server-side instead of trusting a tenant_id from the client.
     * Access is governed by the route permissions, not shop membership.
     *
     * @throws GeneralException
     */
    private function ecommerceTenantId(): int
    {
        $tenantId = Tenant::query()
            ->where('is_ecommerce', true)
            ->where('slug', 'tekzo')
            ->value('id');

        if (! $tenantId) {
            throw new GeneralException('TEKZO e-commerce tenant not found');
        }

        return $tenantId;
    }

    private function getModelClass($model)
    {
        return match ($model) {
            'product' => Product::class,
            'category' => Category::class,
            'brand' => Brand::class,
            default => null,
        };
    }
}

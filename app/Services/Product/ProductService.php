<?php

namespace App\Services\Product;

use App\Exceptions\GeneralException;
use App\Models\Pos\Product\AttributeValue;
use App\Models\Pos\Product\Product;
use App\Models\Pos\Product\ProductImage;
use App\Models\Pos\Product\ProductSerial;
use App\Models\Pos\Product\ProductVariant;
use App\Models\Pos\Product\ProductVideo;
use App\Models\Pos\Product\VariantPrice;
use App\Models\Pos\ProductSpecification\ProductSpecification;
use App\Services\BaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProductService extends BaseService
{
    public function __construct(Product $product)
    {
        $this->model = $product;
    }

    public function createProduct(Request $request): static
    {

        $this->model = $this->model->create([
            'product_name' => $request->name,
            'headline' => $request->headline,
            'long_description' => $request->long_description,
            'short_description' => $request->short_description,
            'tag' => $request->tag ?? null,
            'warranty_day' => $request->warranty_day,
            'booking_amount' => $request->booking_amount,
            'purchase_point' => $request->purchase_point,
            'warranty_text' => $request->warranty_text,
            'service_warranty' => $request->service_warranty,
            'product_image' => $request->thumbnail_image,
            'category_id' => $request->category_id,
            'brand_id' => $request->brand_id,
            'slug' => str_replace([' ', '/', ',', '.'], '-', strtolower($request->name)) ?? null,
            'is_ecommerce' => false,
        ]);

        return $this;
    }

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
                    'tags' => $tags,
                ]);
            } else {
                // Create new record
                $this->model->seos()->create([
                    'tenant_id' => $tenantId,
                    'title' => $title,
                    'description' => $description,
                    'tags' => $tags,
                ]);
            }
        }

        return $this;
    }

    public function productThumbnailImage(Request $request): static
    {
        if ($request->hasFile('thumbnail_image')) {
            $this->model->addMediaFromRequest('thumbnail_image')->toMediaCollection('thumbnail_image');
        }

        return $this;
    }

    public function variantAttribute(Request $request): static
    {
        $attributeIds = collect($request->input('attributes'))
            ->pluck('attribute_id')
            ->flatten()
            ->unique()
            ->values()
            ->toArray();

        foreach ($attributeIds as $attributeId) {
            $this->model->attributes()->firstOrCreate(
                [
                    'attribute_id' => $attributeId,
                    'product_id' => $this->model->id,
                ],
            );
        }

        return $this;
    }

    public function variant(Request $request): static
    {
        $variantsData = $request->input('variants', []);

        if (empty($variantsData)) {
            return $this;
        }

        $variantsToInsert = [];
        $pricesToInsert = [];
        $pivotToInsert = [];

        foreach ($variantsData as $variantData) {
            $variantsToInsert[] = [
                'sku' => $variantData['sku'],
                'product_id' => $this->model->id,
                'stock' => $variantData['stock'] ?? 0,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Insert variants in bulk
        $variantIds = [];
        if ($variantsToInsert !== []) {
            $this->model->variants()->insert($variantsToInsert);

            // Fetch inserted IDs (assuming auto-increment order preserved)
            $lastInsertedIds = $this->model->variants()
                ->latest('id')
                ->take(count($variantsToInsert))
                ->pluck('id')
                ->reverse()
                ->values()
                ->toArray();

            $variantIds = array_combine(array_keys($variantsData), $lastInsertedIds);
        }

        // Now build prices and pivot entries
        foreach ($variantsData as $index => $variantData) {
            $variantId = $variantIds[$index];

            $pricesToInsert[] = [
                'product_variant_id' => $variantId,
                'dealer_price' => $variantData['dealer_price'] ?? null,
                'retail_price' => $variantData['retail_price'] ?? null,
                'max_retail_price' => $variantData['max_retail_price'],
                'discount_percent' => $variantData['discount_percent'] ?? null,
                'discount_fixed' => $variantData['discount_fixed'] ?? null,
                'b2b_price' => $variantData['b2b_price'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // Attach attributes
            if (! empty($variantData['attribute_values'])) {
                foreach ($variantData['attribute_values'] as $attrValueId) {
                    $pivotToInsert[] = [
                        'product_variant_id' => $variantId,
                        'attribute_value_id' => $attrValueId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
        }

        // Bulk insert prices & pivot
        if ($pricesToInsert !== []) {
            VariantPrice::query()->insert($pricesToInsert);
        }

        if ($pivotToInsert !== []) {
            DB::table('variant_attribute_values')->insert($pivotToInsert);
        }

        return $this;
    }

    public function colorAttributeValueImage(Request $request): static
    {
        foreach ($request->color_images as $colorImage) {

            $attributeValue = AttributeValue::query()
                ->where('id', $colorImage['color_id'])
                ->first();

            if (! $attributeValue) {
                continue;
            }
            if (isset($colorImage['featured_image']) && $colorImage['featured_image']) {

                ProductImage::query()->create([
                    'product_id' => $this->model->id,
                    'attribute_value_id' => $attributeValue->id,
                    'image_path' => $colorImage['featured_image'],
                    'alt_text' => 'Featured image for color '.$attributeValue->value,
                    'order' => 0,
                    'is_primary' => true,
                ]);
            }

            // Store slider images
            if (isset($colorImage['slider_images']) && is_array($colorImage['slider_images'])) {
                foreach ($colorImage['slider_images'] as $key => $sliderFile) {

                    ProductImage::query()->create([
                        'product_id' => $this->model->id,
                        'attribute_value_id' => $attributeValue->id,
                        'image_path' => $sliderFile,
                        'alt_text' => 'Gallery image '.($key + 1).' for color '.$attributeValue->value,
                        'order' => $key + 1,
                        'is_primary' => false,
                    ]);
                }
            }
        }

        return $this;
    }

    public function createProductVideos(Request $request): static
    {
        // Product-level video links (no color/attribute)
        foreach ($request->input('video_links', []) as $order => $videoLink) {
            if (empty($videoLink)) {
                continue;
            }
            ProductVideo::query()->create([
                'product_id' => $this->model->id,
                'attribute_value_id' => null,
                'video_link' => $videoLink,
                'order' => $order + 1,
            ]);
        }

        // Per-color video links
        foreach ($request->input('color_images', []) as $colorImage) {
            foreach ($colorImage['video_links'] ?? [] as $order => $videoLink) {
                if (empty($videoLink)) {
                    continue;
                }
                ProductVideo::query()->create([
                    'product_id' => $this->model->id,
                    'attribute_value_id' => $colorImage['color_id'],
                    'video_link' => $videoLink,
                    'order' => $order + 1,
                ]);
            }
        }

        return $this;
    }

    public function removeProductThumbnailImage(Request $request): static
    {
        if ($request->hasFile('thumbnail_image')) {
            $mediaItem = $this->model->getFirstMedia('thumbnail_image');
            $mediaItem?->delete();
        }

        return $this;
    }

    public function syncSpecifications(Request $request): static
    {
        $specs = $request->input('specifications', []);

        foreach ($specs as $spec) {
            ProductSpecification::create([
                'product_id' => $this->model->id,
                'parent_id' => $spec['parent_id'] ?? null,
                'sub_id' => $spec['sub_id'] ?? null,
                'details' => $spec['details'] ?? null,
            ]);
        }

        return $this;
    }

    public function updateProduct($request): static
    {
        $this->model->update([
            'product_name' => $request->name,
            'headline' => $request->headline,
            'long_description' => $request->long_description,
            'short_description' => $request->short_description,
            'specifications' => $request->specifications,
            'tag' => $request->tag,
            'warranty_day' => $request->warranty_day,
            'booking_amount' => $request->booking_amount,
            'purchase_point' => $request->purchase_point,
            'warranty_text' => $request->warranty_text,
            'product_image' => 'image',
            'category_id' => $request->category_id,
            'brand_id' => $request->brand_id,
            'slug' => str_replace([' ', '/', ',', '.'], '-', strtolower($request->name)) ?? null,
            'meta_title' => $request->meta_title,
            'meta_desc' => $request->meta_desc,
        ]);
        $this->model->seo()->updateOrCreate(
            ['model_id' => $this->model->id],
            [
                'title' => $request->meta_title ?? $request->name,
                'description' => $request->meta_desc ?? $request->headline,
            ]
        );

        return $this;
    }

    /**
     * @throws GeneralException
     */
    public function ensureVariantsDeletable(array $variantIds): void
    {
        $existingVariantIds = $this->model->variants()->pluck('id')->toArray();
        $variantsToRemove = array_diff($existingVariantIds, $variantIds);

        if ($variantsToRemove === []) {
            return;
        }

        $serialCount = ProductSerial::query()
            ->whereIn('product_variant_id', $variantsToRemove)
            ->count();

        if ($serialCount === 0) {
            return;
        }

        $attributeLabels = DB::table('variant_attribute_values')
            ->join('attribute_values', 'variant_attribute_values.attribute_value_id', '=', 'attribute_values.id')
            ->whereIn('product_variant_id', $variantsToRemove)
            ->pluck('attribute_values.value')
            ->implode(', ');

        throw new GeneralException(
            "Cannot delete variant(s) [{$attributeLabels}] because serial numbers have already been generated."
        );
    }

    public function productVariantDeletable(): static
    {
        $this->model->variants()->delete();

        return $this;
    }

    public function productDelete(): static
    {
        $this->model->delete();

        return $this;
    }

    public function syncVariants($product, array $variantsData): void
    {
        DB::transaction(function () use ($product, $variantsData) {

            $incomingSignatures = [];

            foreach ($variantsData as $data) {

                $attributeValues = $this->normalizeAttributeValues($data['attribute_values']);

                $signature = $this->makeSignature($attributeValues);
                $incomingSignatures[] = $signature;

                $variant = $this->findVariant($product->id, $signature);

                if ($variant) {

                    // 🔥 UPDATE EXISTING VARIANT (ONLY META)
                    $this->updateVariant($variant, $data);
                } else {

                    // 🆕 CREATE NEW VARIANT
                    $variant = $this->createVariant($product->id, $data, $signature);
                }

                // 💰 PRICE SYNC
                $this->syncPrice($variant->id, $data);

                // 🔗 OPTIONS SYNC
                $variant->attributeValues()->sync($attributeValues);
            }

            // ❌ DEACTIVATE REMOVED VARIANTS
            $this->deactivateMissingVariants($product->id, $incomingSignatures);
        });
    }

    /**
     * FIND VARIANT BY SIGNATURE
     */
    private function findVariant($productId, $signature)
    {
        return ProductVariant::where('product_id', $productId)
            ->where('signature_hash', $signature)
            ->first();
    }

    /**
     * CREATE VARIANT (IMMUTABLE IDENTITY)
     */
    private function createVariant($productId, $data, $signature)
    {
        return ProductVariant::create([
            'product_id' => $productId,
            'sku' => $data['sku'],
            'stock' => $data['stock'] ?? 0,
            'signature_hash' => $signature,
            'status' => 1,
        ]);
    }

    /**
     * UPDATE ONLY META (NOT IDENTITY)
     */
    private function updateVariant($variant, $data)
    {
        $variant->update([
            'sku' => $data['sku'],
            'stock' => $data['stock'] ?? $variant->stock,
            'status' => 1,
        ]);
    }

    /**
     * PRICE ENGINE (VERSIONED)
     */
    private function syncPrice($variantId, $data)
    {
        if (! isset($data['max_retail_price'])) {
            return;
        }

        // close previous price
        // VariantPrice::where('product_variant_id', $variantId)
        //     ->whereNull('ends_at')
        //     ->update(['ends_at' => now()]);

        // new price row
        VariantPrice::create([
            'product_variant_id' => $variantId,
            'dealer_price' => $data['dealer_price'] ?? null,
            'max_retail_price' => $data['max_retail_price'] ?? null,
            'b2b_price' => $data['b2b_price'] ?? null,
            'starts_at' => now(),
        ]);
    }

    /**
     * REMOVE OLD VARIANTS SAFELY
     */
    private function deactivateMissingVariants($productId, array $signatures)
    {
        ProductVariant::where('product_id', $productId)
            ->whereNotIn('signature_hash', $signatures)
            ->update(['status' => 0]);
    }

    /**
     * SIGNATURE GENERATOR
     */
    private function makeSignature(array $values): string
    {
        return collect($values)
            ->map(fn ($v) => (int) $v)
            ->sort()
            ->implode('|');
    }

    /**
     * NORMALIZE INPUT
     */
    private function normalizeAttributeValues($values): array
    {
        if (is_string($values)) {
            return array_map(intval(...), explode(',', $values));
        }

        return array_map(intval(...), (array) $values);
    }

    public function revalidate()
    {
        $this->revalidateFrontend('product', $this->model->slug);

        if ($this->model->category) {
            $this->revalidateFrontend('category', $this->model->category->slug);
        }

        if ($this->model->brand) {
            $this->revalidateFrontend('brand', $this->model->brand->slug);
        }

        $this->revalidateFrontendPage('category');
        $this->revalidateFrontendPage('featured-products');
        $this->revalidateFrontendPage('shop');

        return $this;
    }

    public function revalidateFrontendPage(string $type): void
    {
        $this->sendRevalidationRequest(['type' => $type]);
    }

    public function revalidateFrontend(string $type, ?string $slug = null): void
    {
        $this->sendRevalidationRequest([
            'type' => $type,
            'slug' => $slug ? strtolower($slug) : null,
        ]);
    }

    protected function sendRevalidationRequest(array $payload): void
    {
        $url = config('app.frontend_url').'/api/revalidate';

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

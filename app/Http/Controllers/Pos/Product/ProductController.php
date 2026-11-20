<?php

namespace App\Http\Controllers\Pos\Product;

use App\Enums\ProductStatusEnum;
use App\Exceptions\GeneralException;
use App\Filters\Pos\Product\ProductFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pos\Product\ProductRequest;
use App\Models\Pos\Product\AttributeValue;
use App\Models\Pos\Product\Product;
use App\Models\Pos\Product\ProductAttribute;
use App\Models\Pos\Product\ProductImage;
use App\Models\Pos\Product\ProductVariant;
use App\Models\Pos\Product\ProductVideo;
use App\Models\Pos\Product\VariantPrice;
use App\Services\Product\ProductService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Throwable;

class ProductController extends Controller
{
    public function __construct(protected ProductService $service, protected ProductFilter $filter) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return $this->service
            ->filters($this->filter)
            ->with(['category:id,name', 'brand:id,name'])
            ->select(['id', 'product_name', 'category_id', 'brand_id', 'status', 'a_status', 'product_image', 'created_at', 'has_official_warranty', 'is_ecommerce', 'is_chinese', 'is_unofficial', 'is_refurbished'])
            ->orderByDesc('id')
            ->paginate(request('per_page', 10))
            ->through(fn($order) => $this->transformOrder($order));
    }

    private function transformOrder(Product $product): array
    {
        $statusEnum = ProductStatusEnum::from($product->status);
        $productTypeStatus = ProductStatusEnum::from($product->a_status);

        return [
            ...$product->toArray(),
            'status' => [
                'id' => $product->status,
                'name' => $statusEnum->name,
                'color' => $statusEnum->color(),
            ],
            'type_status' => [
                'name' => $productTypeStatus->name,
                'color' => $productTypeStatus->color(),
            ]
        ];
    }


    /**
     * @throws Throwable
     * @throws GeneralException
     */
    public function store(ProductRequest $request)
    {
        try {
            DB::beginTransaction();

            $this->service
                ->createProduct($request)
                ->variantAttribute($request)
                ->variant($request)
                ->colorAttributeValueImage($request)
                ->createProductVideos($request)
                ->createSeo($request)
                ->revalidate();

            DB::commit();

            return successResponse('Product created successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            if (app()->environment() == 'production') {
                throw new GeneralException('Product creation failed', 500);
            }
            throw new GeneralException($e->getMessage(), 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        $product->load([
            'attributes.attribute',
            'variants' => function ($q) {
                $q->where('status', '1');
            },
            'variants.attributeValues',
            'variants.price'
        ]);

        $imageMap = $product->productImages
            ->keyBy('attribute_value_id');

        $productVideoLinks = ProductVideo::query()
            ->where('product_id', $product->id)
            ->whereNull('attribute_value_id')
            ->orderBy('order')
            ->pluck('video_link')
            ->toArray();

        return [
            ...$product->toArray(),
            'name' => $product->product_name,
            'thumbnail_image' => $product->product_image,
            'video_links' => $productVideoLinks,
            'attributes' => $product->attributes ? $product->attributes->map(function ($attribute) use ($product) {

                $variantAttributeValuesIds = $this->variantAttributeValues(
                    $product->variants->pluck('id')->toArray(),
                    $attribute->attribute_id
                );
                return [
                    'name' => $attribute->attribute->name,
                    'attribute_id' => $attribute->attribute->id,
                    'attribute_value_id' => array_map('intval', $variantAttributeValuesIds),
                    'values' => $this->attributeValues($attribute->attribute->id),
                    'images' => $this->attributeValueProductFeaturedImage($variantAttributeValuesIds, $product->id)
                        ->sortBy(fn($img) => array_search($img['id'], $variantAttributeValuesIds))->values(),
                ];
            }) : [],
            'variants' => $product->variants->map(function ($variant) use ($imageMap) {
                $attributeValueIds = $variant->attributeValues
                    ->pluck('pivot.attribute_value_id')
                    ->map(fn($id) => (int)$id)
                    ->toArray();

                $price = $variant->price;

                return [
                    'id' => (int)$variant->id,
                    'sku' => $variant->sku,
                    'stock' => $variant->stock,
                    'image' => $this->resolveVariantImage($variant, $imageMap),
                    'attribute_values' => $attributeValueIds,
                    'max_retail_price' => $price->max_retail_price,
                    'discount_percent' => $price->discount_percent,
                    'discount_fixed' => $price->discount_fixed,
                ];
            }),


        ];
    }


    public function attributeValueProductFeaturedImage($ids, $productId): \Illuminate\Database\Eloquent\Collection|\Illuminate\Support\Collection
    {
        return AttributeValue::query()
            ->whereIn('id', $ids)
            ->select(['id', 'value'])
            ->get()
            ->map(function ($attributeValue) use ($productId) {
                return [
                    ...$attributeValue->toArray(),
                    'featured_image' => $this->getFeaturedImage($attributeValue, $productId),
                    'slider_images' => $this->getSliderImagesByProduct($attributeValue, $productId),
                    'video_links' => $this->getVideoLinks($attributeValue, $productId),
                ];
            });
    }

    private function getFeaturedImage($attributeValue, $productId)
    {
        $image = ProductImage::query()
            ->where('attribute_value_id', $attributeValue->id)
            ->where('product_id', $productId)
            ->where('is_primary', true)
            ->first();

        return $image?->image_path;
    }

    private function getVideoLinks($attributeValue, $productId): array
    {
        return ProductVideo::query()
            ->where('attribute_value_id', $attributeValue->id)
            ->where('product_id', $productId)
            ->orderBy('order')
            ->get(['id', 'video_link'])
            ->map(fn($v) => ['id' => $v->id, 'video_link' => $v->video_link])
            ->toArray();
    }

    private function getSliderImagesByProduct($attributeValue, $productId)
    {
        $images = ProductImage::query()
            ->where('attribute_value_id', $attributeValue->id)
            ->where('product_id', $productId)
            ->where('is_primary', false)
            ->orderBy('order')
            ->get();

        return $images->map(function ($image) {
            return [
                'id' => $image->id,
                'image_path' => $image->image_path,
            ];
        });
    }

    private function variantAttributeValues($ids, $attributeId)
    {
        return DB::table('variant_attribute_values')
            ->whereIn('product_variant_id', $ids)
            ->join(
                'attribute_values',
                'variant_attribute_values.attribute_value_id',
                '=',
                'attribute_values.id',
            )
            ->where('attribute_values.attribute_id', $attributeId)
            ->pluck('attribute_values.id')
            ->map(fn($id) => (int)$id)
            ->unique()
            ->values()
            ->toArray();
    }

    private function attributeValues($attributeId): \Illuminate\Database\Eloquent\Collection
    {
        return AttributeValue::query()
            ->where('attribute_id', $attributeId)
            ->select(['id', 'attribute_id', 'value', 'status'])
            ->get();
    }

    /**
     * Update the specified resource in storage.
     * @throws GeneralException|Throwable
     */
    public function update(ProductRequest $request, Product $product)
    {
        try {
            DB::beginTransaction();

            // -------------------------
            // Update product info
            // -------------------------
            $product->update([
                'product_name'      => $request->name,
                'headline'          => $request->headline,
                'long_description'  => $request->long_description,
                'short_description' => $request->short_description,
                'warranty_day'      => $request->warranty_day,
                'booking_amount'    => $request->booking_amount,
                'purchase_point'    => $request->purchase_point,
                'warranty_text'     => $request->warranty_text,
                'service_warranty'  => $request->service_warranty,
                'product_image'     => $request->thumbnail_image,
                'category_id'       => $request->category_id,
                'brand_id'          => $request->brand_id,
                'slug'              => str_replace([' ', '/', ',', '.'], '-', strtolower($request->name)) ?? null,
            ]);

            // -------------------------
            // 1. Sync Product Attributes
            // -------------------------
            $attributeIds = collect($request->input('attributes'))
                ->pluck('attribute_id')
                ->unique()
                ->values()
                ->toArray();

            ProductAttribute::query()
                ->where('product_id', $product->id)
                ->whereNotIn('attribute_id', $attributeIds)
                ->delete();

            foreach ($attributeIds as $attributeId) {
                ProductAttribute::query()->updateOrCreate(
                    ['product_id' => $product->id, 'attribute_id' => $attributeId],
                    ['updated_at' => now()]
                );
            }

            // -------------------------
            // 2. Sync Variants
            //    Match by attribute-value combination, NOT by id
            // -------------------------
            $requestVariants = $request->input('variants', []);

            $normalizedRequestVariants = collect($requestVariants)->map(function ($variantData) {
                $vals = is_array($variantData['attribute_values'])
                    ? $variantData['attribute_values']
                    : explode(',', $variantData['attribute_values']);

                sort($vals);

                return array_merge($variantData, ['_normalized_values' => $vals]);
            });

            // Active + Inactive সব existing variants load করো
            $existingVariants = ProductVariant::query()
                ->where('product_id', $product->id)
                ->with('attributeValues')
                ->get();

            $matchedExistingIds = [];

            foreach ($normalizedRequestVariants as $variantData) {
                $requestedVals = $variantData['_normalized_values'];

                $matched = $existingVariants->first(function ($existing) use ($requestedVals) {
                    $existingVals = $existing->attributeValues->pluck('id')->sort()->values()->toArray();
                    return $existingVals == $requestedVals;
                });

                if ($matched) {
                    // Exist করলে update করো + status active করো
                    $matched->update([
                        'sku'    => $variantData['sku'],
                        'stock'  => $variantData['stock'],
                        'status' => '1',
                    ]);
                    $variant = $matched;
                    $matchedExistingIds[] = $matched->id;
                } else {
                    // নতুন হলে create করো
                    $variant = ProductVariant::query()->create([
                        'product_id' => $product->id,
                        'sku'        => $variantData['sku'],
                        'stock'      => $variantData['stock'],
                        'status'     => '1',
                    ]);
                    $matchedExistingIds[] = $variant->id;
                }

                $variant->attributeValues()->sync($requestedVals);

                // -------------------------
                // 3. Sync Prices
                // -------------------------
                VariantPrice::query()->updateOrCreate(
                    ['product_variant_id' => $variant->id],
                    [
                        'dealer_price'     => $variantData['dealer_price'] ?? null,
                        'retail_price'     => $variantData['retail_price'] ?? null,
                        'max_retail_price' => $variantData['max_retail_price'] ?? null,
                        'discount_percent' => $variantData['discount_percent'] ?? null,
                        'discount_fixed'   => $variantData['discount_fixed'] ?? null,
                        'b2b_price'        => $variantData['b2b_price'] ?? null,
                    ]
                );
            }


            // -------------------------
            $variantsToDeactivate = ProductVariant::query()
                ->where('product_id', $product->id)
                ->whereNotIn('id', $matchedExistingIds)
                ->get();

            foreach ($variantsToDeactivate as $inactiveVariant) {
                // Variant inactive করো
                $inactiveVariant->update(['status' => '0']);

                // Price soft delete করো
                VariantPrice::query()
                    ->where('product_variant_id', $inactiveVariant->id)
                    ->each(fn($price) => $price->delete());
            }

            // -------------------------
            // 4. Sync Color Images
            // -------------------------
            if (isset($request->color_images)) {
                foreach ($request->color_images as $colorImage) {

                    $attributeValue = AttributeValue::query()->find($colorImage['color_id']);
                    if (!$attributeValue) continue;

                    if (!empty($colorImage['featured_image'])) {
                        ProductImage::query()
                            ->where('product_id', $product->id)
                            ->where('attribute_value_id', $attributeValue->id)
                            ->where('is_primary', true)
                            ->delete();

                        ProductImage::query()->create([
                            'product_id'         => $product->id,
                            'attribute_value_id' => $attributeValue->id,
                            'image_path'         => $colorImage['featured_image'],
                            'is_primary'         => true,
                            'alt_text'           => 'Featured image for color ' . $attributeValue->value,
                            'order'              => 0,
                        ]);
                    }

                    foreach ($colorImage['slider_images'] ?? [] as $key => $sliderFile) {
                        $exists = ProductImage::query()
                            ->where('product_id', $product->id)
                            ->where('attribute_value_id', $attributeValue->id)
                            ->where('image_path', $sliderFile)
                            ->where('is_primary', false)
                            ->exists();

                        if (!$exists) {
                            ProductImage::query()->create([
                                'product_id'         => $product->id,
                                'attribute_value_id' => $attributeValue->id,
                                'image_path'         => $sliderFile,
                                'alt_text'           => 'Slider image for color ' . $attributeValue->value,
                                'order'              => $key + 1,
                                'is_primary'         => false,
                            ]);
                        }
                    }
                }
            }

            // -------------------------
            // 5. Remove Slider Images
            // -------------------------
            if (!empty($request->remove_slider_images)) {
                $imageIds = collect($request->input('remove_slider_images'))->toArray();
                ProductImage::query()->whereIn('id', $imageIds)->delete();
            }

            // -------------------------
            // 6. Sync Product Videos
            // -------------------------

            // Product-level video links (attribute_value_id = null)
            foreach ($request->input('video_links', []) as $order => $videoLink) {
                if (empty($videoLink)) continue;
                $exists = ProductVideo::query()
                    ->where('product_id', $product->id)
                    ->whereNull('attribute_value_id')
                    ->where('video_link', $videoLink)
                    ->exists();
                if (!$exists) {
                    ProductVideo::query()->create([
                        'product_id'         => $product->id,
                        'attribute_value_id' => null,
                        'video_link'         => $videoLink,
                        'order'              => $order + 1,
                    ]);
                }
            }

            // Remove product-level videos that are no longer in the list
            if ($request->has('video_links')) {
                ProductVideo::query()
                    ->where('product_id', $product->id)
                    ->whereNull('attribute_value_id')
                    ->whereNotIn('video_link', array_filter($request->input('video_links', [])))
                    ->delete();
            }

            // Per-color video links
            if (isset($request->color_images)) {
                foreach ($request->color_images as $colorImage) {
                    if (!empty($colorImage['remove_video_links'])) {
                        ProductVideo::query()
                            ->where('product_id', $product->id)
                            ->whereIn('id', $colorImage['remove_video_links'])
                            ->delete();
                    }
                    foreach ($colorImage['video_links'] ?? [] as $order => $videoLink) {
                        if (empty($videoLink)) continue;
                        $exists = ProductVideo::query()
                            ->where('product_id', $product->id)
                            ->where('attribute_value_id', $colorImage['color_id'])
                            ->where('video_link', $videoLink)
                            ->exists();
                        if (!$exists) {
                            ProductVideo::query()->create([
                                'product_id'         => $product->id,
                                'attribute_value_id' => $colorImage['color_id'],
                                'video_link'         => $videoLink,
                                'order'              => $order + 1,
                            ]);
                        }
                    }
                }
            }

            // -------------------------
            // 7. Sync Product Seo
            // -------------------------
            $this->service->setModel($product)->revalidate();

            DB::commit();
            return successResponse('Product updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            if (app()->environment() === 'production') {
                throw new GeneralException('Product update failed', 500);
            }
            throw new GeneralException($e->getMessage(), 500);
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        $this->service
            ->setModel($product)
            ->productVariantDeletable()
            ->productDelete()
            ->revalidate();
        return successResponse('Product deleted successfully');
    }

    /**
     * @throws GeneralException
     */
    public function status(Request $request, Product $product)
    {
        $request->validate([
            'status' => ['required', Rule::in([
                ProductStatusEnum::Active->value,
                ProductStatusEnum::Inactive->value,
                ProductStatusEnum::Approved->value,
                ProductStatusEnum::Arrival->value,
                ProductStatusEnum::Comming->value,
                ProductStatusEnum::Featured->value,
            ])]
        ]);


        if (in_array($request->type, ['active', 'inactive'])) {
            if ($product->status == $request->status) {
                throw new GeneralException('Product status is already in use');
            }

            $product->update([
                'status' => $request->status
            ]);

            $this->service->setModel($product)->revalidate();

            return successResponse('Product status updated successfully');
        }

        if ($product->a_status == $request->status) {
            throw new GeneralException('Product status is already in use');
        }

        $product->update([
            'a_status' => $request->status
        ]);

        $this->service->setModel($product)->revalidate();

        return successResponse('Product status updated successfully');
    }

    public function toggleOfficialWarranty(Product $product)
    {
        $product->update([
            'has_official_warranty' => !$product->has_official_warranty,
        ]);

        $label = $product->has_official_warranty ? 'added' : 'removed';

        $this->service->setModel($product)->revalidate();

        return successResponse("Official warranty badge {$label} successfully");
    }

    public function toggleEcommerce(Product $product)
    {
        $product->update([
            'is_ecommerce' => !$product->is_ecommerce,
        ]);

        $label = $product->is_ecommerce ? 'enabled' : 'disabled';

        $this->service->setModel($product)->revalidate();

        return successResponse("Ecommerce visibility {$label} successfully");
    }

    public function toggleChinese(Product $product)
    {
        $product->update([
            'is_chinese' => !$product->is_chinese,
        ]);

        $label = $product->is_chinese ? 'added' : 'removed';

        $this->service->setModel($product)->revalidate();

        return successResponse("Chinese badge {$label} successfully");
    }

    public function toggleUnofficial(Product $product)
    {
        $product->update([
            'is_unofficial' => !$product->is_unofficial,
        ]);

        $label = $product->is_unofficial ? 'added' : 'removed';

        $this->service->setModel($product)->revalidate();

        return successResponse("Unofficial badge {$label} successfully");
    }

    public function toggleRefurbished(Product $product)
    {
        $product->update([
            'is_refurbished' => !$product->is_refurbished,
        ]);

        $label = $product->is_refurbished ? 'added' : 'removed';

        $this->service->setModel($product)->revalidate();

        return successResponse("Refurbished badge {$label} successfully");
    }

    public function showVariant(Product $product)
    {
        $product->load([
            'variants' => function ($q) {
                $q->where('status', '1');
            },
            'variants.attributeValues',
            'variants.price',
            'productImages'
        ]);

        $imageMap = $product->productImages
            ->keyBy('attribute_value_id');

        return [
            ...$product->toArray(),
            'name'            => $product->product_name,
            'thumbnail_image' => $product->product_image,
            'variants'        => $product->variants->map(fn($variant) => [
                'id'               => (int) $variant->id,
                'sku'              => $variant->sku,
                'stock'            => $variant->stock,
                'is_prebook'       => $variant->is_prebook,
                'image'            => $this->resolveVariantImage($variant, $imageMap),
                'attribute_values' => $variant->attributeValues,
                'dealer_price'     => $variant->price->dealer_price,
                'b2b_price'        => $variant->price->b2b_price,
                'max_retail_price' => $variant->price->max_retail_price,
                'discount_percent' => $variant->price->discount_percent,
                'discount_fixed'   => $variant->price->discount_fixed,
                'sale_price'       => max(0, $variant->price->max_retail_price - ($variant->price->discount_fixed ?? 0)),
            ]),
        ];
    }

    private function resolveVariantImage($variant, $imageMap): ?string
    {
        return $variant->attributeValues
            ->map(fn($av) => $imageMap->get($av->id)?->image_path)
            ->filter()
            ->first();
    }

    /**
     * @throws Throwable
     * @throws GeneralException
     */
    public function updateVariant(Request $request)
    {
        $request->validate([
            'id'               => 'required|exists:product_variants,id',
            'stock'            => 'required',
            'max_retail_price' => 'required|numeric',
            'sale_price'       => 'required|numeric',
        ]);

        $variant = ProductVariant::query()
            ->with('product')
            ->find($request->id);

        $discountFixed = max(0, $request->max_retail_price - $request->sale_price);

        try {
            DB::beginTransaction();

            $variant->update([
                'stock' => filter_var($request->stock, FILTER_VALIDATE_BOOLEAN) ? 1 : 0,
                'is_prebook' => filter_var($request->is_prebook, FILTER_VALIDATE_BOOLEAN),
            ]);

            VariantPrice::query()->updateOrCreate(
                ['product_variant_id' => $variant->id],
                [
                    'max_retail_price' => $request->max_retail_price,
                    'discount_percent' => null,
                    'discount_fixed'   => $discountFixed,
                ]
            );

            $this->service->setModel($variant->product)->revalidate();

            DB::commit();
            return successResponse('Variant updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            throw new GeneralException($e->getMessage(), 500);
        }
    }
}

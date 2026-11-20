<?php

namespace App\Http\Controllers\Ecommerce\Offer;

use App\Exceptions\GeneralException;
use App\Http\Controllers\Controller;
use App\Models\Ecommerce\Offer\Offer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Throwable;

class OfferController extends Controller
{
    public function index()
    {
        return Offer::query()
            ->withCount('products')
            ->orderByDesc('id')
            ->paginate(request('per_page', 10));
    }

    /**
     * @throws Throwable
     * @throws GeneralException
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => ['nullable', 'string'],
            'banner' => ['nullable', 'string'],
            'type' => ['required', 'in:campaign,flash_sale,coupon'],
            'start_at' => ['required', 'date'],
            'end_at' => ['required', 'date'],
            'priority' => ['nullable', 'integer'],
            'is_active' => ['required', 'boolean'],
            'products' => ['required', 'array', 'min:1'],
            'products.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'products.*.product_variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'products.*.discount_type' => ['required', 'in:fixed,percentage'],
            'products.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
            'products.*.special_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        // // Ensure either product_id or product_variant_id is set, not both or neither
        // foreach ($data['products'] as $product) {
        //     if (empty($product['product_id']) && empty($product['product_variant_id'])) {
        //         throw new GeneralException('Each product must have either product_id or product_variant_id.');
        //     }
        //     if (!empty($product['product_id']) && !empty($product['product_variant_id'])) {
        //         throw new GeneralException('Cannot set both product_id and product_variant_id for the same offer item.');
        //     }
        // }

        try {
            DB::beginTransaction();
            $offer = Offer::query()->create([
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'banner' => $data['banner'] ?? null,
                'type' => $data['type'],
                'start_at' => $data['start_at'] ?? null,
                'end_at' => $data['end_at'] ?? null,
                'priority' => $data['priority'] ?? 0,
                'is_active' => (bool) $data['is_active'],
            ]);

            $offer->products()->createMany(collect($data['products'])->map(fn ($product) => [
                'product_id' => $product['product_id'] ?? null,
                'product_variant_id' => $product['product_variant_id'] ?? null,
                'discount_type' => $product['discount_type'],
                'discount_amount' => $product['discount_amount'] ?? null,
                'special_price' => $product['special_price'] ?? null,
            ])->all());

            DB::commit();

            return successResponse('Offer created successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            throw new GeneralException(app()->isProduction() ? 'Failed to create offer' : $e->getMessage(), 500, $e);
        }
    }

    public function show($id)
    {
        $offer = Offer::query()
            ->with([
                'products.product',
                'products.productVariant.price',
                'products.productVariant.attributeValues',
            ])
            ->findOrFail($id);

        $products = $offer->products->map(function ($item) {
            $product = $item->product;
            $variant = $item?->productVariant;
            $price = $variant?->price;
            $attributeValues = $variant?->attributeValues
                ->pluck('value')
                ->implode('/');
            $variantLabel = $variant?->attributeValues
                ->pluck('value')
                ->implode('/');

            return [
                'id' => $item->id,
                'offer_id' => $item->offer_id,
                'product_id' => $item->product_id,
                'product_variant_id' => $item->product_variant_id,
                'discount_type' => $item->discount_type,
                'discount_amount' => $item->discount_amount,
                'special_price' => $item->special_price,
                'sku' => $variant?->sku,
                'stock' => $variant?->stock,
                'max_retail_price' => $price?->max_retail_price ?? 0,
                'attribute_values' => $attributeValues,
                'variant_label' => $variantLabel,
                'product' => [
                    'id' => $product?->id,
                    'product_name' => $product?->product_name,
                    'headline' => $product?->headline,
                    'slug' => $product?->slug,
                    'product_image' => $product?->product_image,
                ],
            ];
        });

        return response()->json([
            'id' => $offer->id,
            'title' => $offer->title,
            'slug' => $offer->slug,
            'description' => $offer->description,
            'banner' => $offer->banner,
            'type' => $offer->type,
            'start_at' => $offer->start_at,
            'end_at' => $offer->end_at,
            'is_active' => $offer->is_active,
            'products' => $products,
        ]);
    }

    public function update(Request $request, Offer $offer)
    {
        $data = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'banner' => 'nullable|string',
            'type' => 'sometimes|required|in:campaign,flash_sale,coupon',
            'start_at' => 'nullable|date',
            'end_at' => 'nullable|date',
            'priority' => 'nullable|integer',
            'is_active' => 'boolean',
            'products' => ['sometimes', 'array'],
            // Scoped to this offer so one offer's payload can't be used to
            // edit/delete another offer's line items by guessing ids.
            'products.*.id' => ['nullable', 'integer', Rule::exists('offer_products', 'id')->where('offer_id', $offer->id)],
            'products.*.product_id' => ['nullable', 'integer', 'exists:products,id'],
            'products.*.product_variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'products.*.discount_type' => ['required_with:products', 'in:fixed,percentage'],
            'products.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
            'products.*.special_price' => ['nullable', 'numeric', 'min:0'],
            'deleted_product_ids' => ['sometimes', 'array'],
            'deleted_product_ids.*' => ['integer', Rule::exists('offer_products', 'id')->where('offer_id', $offer->id)],
        ]);

        try {
            DB::beginTransaction();

            $offer->update(collect($data)->except(['products', 'deleted_product_ids'])->toArray());

            // Only the rows the user actually removed in the UI get deleted -
            // everything else is left untouched (no more wipe-and-recreate,
            // which used to drop every line item on every save).
            if (! empty($data['deleted_product_ids'])) {
                $offer->products()->whereIn('id', $data['deleted_product_ids'])->delete();
            }

            foreach ($data['products'] ?? [] as $product) {
                $payload = [
                    'product_id' => $product['product_id'] ?? null,
                    'product_variant_id' => $product['product_variant_id'] ?? null,
                    'discount_type' => $product['discount_type'],
                    'discount_amount' => $product['discount_amount'] ?? null,
                    'special_price' => $product['special_price'] ?? null,
                ];

                if (! empty($product['id'])) {
                    $offer->products()->where('id', $product['id'])->update($payload);
                } else {
                    $offer->products()->create($payload);
                }
            }

            DB::commit();

            return successResponse('Offer updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            throw new GeneralException(app()->isProduction() ? 'Failed to update offer' : $e->getMessage(), 500, $e);
        }
    }

    public function destroy(Offer $offer)
    {
        try {
            DB::beginTransaction();

            $offer->products()->delete();
            $offer->delete();
            DB::commit();

            return successResponse('Offer deleted successfully');
        } catch (\Exception $e) {
            throw new GeneralException(app()->isProduction() ? 'Failed to delete offer' : $e->getMessage(), 500, $e);
        }
    }
}

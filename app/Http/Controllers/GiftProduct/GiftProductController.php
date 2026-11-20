<?php

namespace App\Http\Controllers\GiftProduct;

use App\Exceptions\GeneralException;
use App\Http\Controllers\Controller;
use App\Models\Product\GiftProduct;
use App\Models\Product\GiftProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GiftProductController extends Controller
{
    public function index()
    {
        return GiftProduct::query()
            ->with([
                'product:id,product_name',
                'preOwned:id,product_id,serial_number',
                'preOwned.product:id,product_name',
                'giftVariants.product:id,product_name',
                'giftVariants.productVariant.attributeValues.attribute',
                'giftVariants.productVariant.price',
            ])
            ->when(request('pre_owned_id'), fn ($q) => $q->where('pre_owned_id', request('pre_owned_id')))
            ->orderBy('id', 'desc')
            ->paginate(request()->get('pageSize', 10));
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'nullable|integer|exists:products,id',
            'pre_owned_id' => 'nullable|integer|exists:pre_owneds,id',
            'is_active' => 'required|boolean',
            'variants' => 'required|array|min:1',
            'variants.*.product_id' => 'required|integer|exists:products,id',
            'variants.*.product_variant_id' => 'nullable|integer|exists:product_variants,id',
            'variants.*.qty' => 'nullable|integer|min:1',
        ]);

        if (! $request->product_id && ! $request->pre_owned_id) {
            return response()->json(['message' => 'Either product or pre-owned product is required.'], 422);
        }

        $existsQuery = GiftProduct::query();
        if ($request->pre_owned_id) {
            $existsQuery->where('pre_owned_id', $request->pre_owned_id);
        } else {
            $existsQuery->where('product_id', $request->product_id);
        }

        if ($existsQuery->exists()) {
            return response()->json([
                'message' => 'This product already has a gift configuration.',
            ], 400);
        }

        DB::beginTransaction();
        try {
            $giftProduct = GiftProduct::create([
                'product_id' => $request->product_id,
                'pre_owned_id' => $request->pre_owned_id,
                'is_active' => $request->is_active,
            ]);

            foreach ($request->variants as $variantData) {
                GiftProductVariant::create([
                    'gift_product_id' => $giftProduct->id,
                    'product_id' => $variantData['product_id'],
                    'product_variant_id' => $variantData['product_variant_id'] ?? null,
                    'qty' => $variantData['qty'] ?? 1,
                ]);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            if (app()->environment('production')) {
                throw new GeneralException('Failed to create gift product. Please try again later.', $e->getCode(), $e);
            }
            throw new GeneralException($e->getMessage(), 500, $e);
        }

        return response()->json(
            $giftProduct->load([
                'product:id,product_name',
                'giftVariants.product:id,product_name',
                'giftVariants.productVariant.attributeValues.attribute',
            ]),
            201
        );
    }

    public function show(GiftProduct $giftProduct)
    {
        return $giftProduct->load([
            'product:id,product_name',
            'preOwned:id,product_id,serial_number',
            'preOwned.product:id,product_name',
            'giftVariants.product:id,product_name',
            'giftVariants.productVariant.attributeValues.attribute',
            'giftVariants.productVariant.price',
        ]);
    }

    public function update(Request $request, GiftProduct $giftProduct)
    {
        $request->validate([
            'product_id' => 'nullable|integer|exists:products,id',
            'pre_owned_id' => 'nullable|integer|exists:pre_owneds,id',
            'is_active' => 'sometimes|required|boolean',
            'variants' => 'sometimes|required|array|min:1',
            'variants.*.product_id' => 'required_with:variants|integer|exists:products,id',
            'variants.*.product_variant_id' => 'nullable|integer|exists:product_variants,id',
            'variants.*.qty' => 'nullable|integer|min:1',
        ]);

        if ($request->has('pre_owned_id') && $request->pre_owned_id != $giftProduct->pre_owned_id) {
            if (GiftProduct::where('pre_owned_id', $request->pre_owned_id)->where('id', '!=', $giftProduct->id)->exists()) {
                return response()->json(['message' => 'This pre-owned product already has a gift configuration.'], 400);
            }
        }

        if ($request->has('product_id') && $request->product_id && $request->product_id != $giftProduct->product_id) {
            if (GiftProduct::where('product_id', $request->product_id)->where('id', '!=', $giftProduct->id)->exists()) {
                return response()->json([
                    'message' => 'This product already has a gift configuration.',
                ], 400);
            }
        }

        DB::beginTransaction();
        try {
            $giftProduct->update($request->only('product_id', 'pre_owned_id', 'is_active'));

            if ($request->has('variants')) {
                $giftProduct->giftVariants()->delete();

                foreach ($request->variants as $variantData) {
                    GiftProductVariant::create([
                        'gift_product_id' => $giftProduct->id,
                        'product_id' => $variantData['product_id'],
                        'product_variant_id' => $variantData['product_variant_id'] ?? null,
                        'qty' => $variantData['qty'] ?? 1,
                    ]);
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            if (app()->environment('production')) {
                throw new GeneralException('Failed to update gift product. Please try again later.', 500, $e);
            }
            throw new GeneralException($e->getMessage(), 500, $e);
        }

        return response()->json(
            $giftProduct->load([
                'product:id,product_name',
                'giftVariants.product:id,product_name',
                'giftVariants.productVariant.attributeValues.attribute',
            ])
        );
    }

    public function destroy(GiftProduct $giftProduct)
    {
        if ($giftProduct->orderItems()->exists()) {
            return response()->json([
                'message' => 'This gift product is already used in orders and cannot be deleted.',
            ], 400);
        }

        DB::beginTransaction();
        try {
            $giftProduct->giftVariants()->delete();
            $giftProduct->delete();
            DB::commit();
        } catch (\Throwable) {
            DB::rollBack();

            return response()->json(['message' => 'Failed to delete gift product.'], 500);
        }

        return response()->json(['message' => 'Gift product deleted successfully.']);
    }

    public function statusToggle(GiftProduct $giftProduct)
    {
        $giftProduct->update(['is_active' => ! $giftProduct->is_active]);

        return response()->json([
            'message' => 'Gift product status updated successfully.',
            'is_active' => $giftProduct->is_active,
        ]);
    }

    public function getByPreOwned(int $preOwnedId)
    {
        $giftProduct = GiftProduct::where('pre_owned_id', $preOwnedId)
            ->where('is_active', true)
            ->with([
                'giftVariants.product:id,product_name',
                'giftVariants.productVariant.attributeValues.attribute',
                'giftVariants.productVariant.price',
            ])
            ->first();

        if (! $giftProduct) {
            return response()->json(['message' => 'No active gift found for this pre-owned product.'], 404);
        }

        return response()->json($giftProduct);
    }

    public function getByProduct(int $productId)
    {
        $giftProduct = GiftProduct::where('product_id', $productId)
            ->where('is_active', true)
            ->with([
                'giftVariants.product:id,product_name',
                'giftVariants.productVariant.attributeValues.attribute',
                'giftVariants.productVariant.price',
            ])
            ->first();

        if (! $giftProduct) {
            return response()->json(['message' => 'No active gift found for this product.'], 404);
        }

        return response()->json($giftProduct);
    }
}

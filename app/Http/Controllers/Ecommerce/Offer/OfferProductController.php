<?php

namespace App\Http\Controllers\Ecommerce\Offer;

use App\Http\Controllers\Controller;
use App\Models\Ecommerce\Offer\OfferProduct;
use Illuminate\Http\Request;

class OfferProductController extends Controller
{
    public function index()
    {
        return response()->json(OfferProduct::with('offer')->paginate(20));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'offer_id' => 'required|exists:offers,id',
            'product_id' => 'nullable|integer|exists:products,id',
            'product_variant_id' => 'nullable|integer|exists:product_variants,id',
            'discount_type' => 'required|in:fixed,percentage',
            'discount_amount' => 'nullable|numeric',
            'special_price' => 'nullable|numeric',
        ]);
        if (empty($data['product_id']) && empty($data['product_variant_id'])) {
            return response()->json(['message' => 'Either product_id or product_variant_id is required.'], 422);
        }
        if (!empty($data['product_id']) && !empty($data['product_variant_id'])) {
            return response()->json(['message' => 'Cannot set both product_id and product_variant_id.'], 422);
        }
        $offerProduct = OfferProduct::create($data);
        return response()->json($offerProduct->fresh('offer'), 201);
    }

    public function show($id)
    {
        $offerProduct = OfferProduct::with('offer')->findOrFail($id);
        return response()->json($offerProduct);
    }

    public function update(Request $request, $id)
    {
        $offerProduct = OfferProduct::findOrFail($id);
        $data = $request->validate([
            'offer_id' => 'sometimes|required|exists:offers,id',
            'product_id' => 'nullable|integer|exists:products,id',
            'product_variant_id' => 'nullable|integer|exists:product_variants,id',
            'discount_type' => 'sometimes|required|in:fixed,percentage',
            'discount_amount' => 'nullable|numeric',
            'special_price' => 'nullable|numeric',
        ]);
        if ((isset($data['product_id']) && $data['product_id']) && (isset($data['product_variant_id']) && $data['product_variant_id'])) {
            return response()->json(['message' => 'Cannot set both product_id and product_variant_id.'], 422);
        }
        if ((empty($data['product_id']) || !$data['product_id']) && (empty($data['product_variant_id']) || !$data['product_variant_id'])) {
            return response()->json(['message' => 'Either product_id or product_variant_id is required.'], 422);
        }
        $offerProduct->update($data);
        return response()->json($offerProduct->fresh(['offer', 'product', 'productVariant']));
    }

    public function destroy($id)
    {
        $offerProduct = OfferProduct::findOrFail($id);
        $offerProduct->delete();
        return response()->json(['message' => 'Offer product deleted']);
    }
}

<?php

namespace App\Http\Controllers\Pos\Product;

use App\Http\Controllers\Controller;
use App\Models\Pos\Product\Product;
use App\Models\Pos\Product\ProductCare;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductCareController extends Controller
{
    public function index(Product $product): JsonResponse
    {
        return response()->json(
            $product->cares()->with('care:id,name,value')->get()
        );
    }

    public function store(Request $request, Product $product): JsonResponse
    {
        $request->validate([
            'care_items' => ['required', 'array', 'min:1'],
            'care_items.*.care_id' => ['required', 'integer', 'exists:cares,id'],
        ]);

        $product->cares()->delete();

        $cares = collect($request->care_items)->map(fn ($item) => [
            'product_id' => $product->id,
            'care_id' => $item['care_id'],
            'created_at' => now(),
            'updated_at' => now(),
        ])->toArray();

        ProductCare::insert($cares);

        return response()->json(['message' => 'Product care saved successfully.']);
    }
}

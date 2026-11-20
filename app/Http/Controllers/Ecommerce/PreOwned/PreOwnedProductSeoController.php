<?php

namespace App\Http\Controllers\Ecommerce\PreOwned;

use App\Http\Controllers\Controller;
use App\Models\Pos\Product\Product;
use Illuminate\Http\Request;

class PreOwnedProductSeoController extends Controller
{
    public function index()
    {
        return Product::query()
            ->whereHas('preOwneds')
            ->with(['category:id,name', 'brand:id,name'])
            ->orderByDesc('id')
            ->paginate(request('per_page', 10));
    }

    public function show(Product $product)
    {
        $seo = $product?->preOwnedSeo;

        return [
            'title' => $product->product_name,
            'slug' => $product->slug,
            'meta_title' => $seo?->title,
            'meta_description' => $seo?->description,
            'tags' => $seo?->tags,
            'description' => $seo?->long_description,
        ];
    }

    public function update(Request $request, Product $product)
    {
        $request->validate([
            'meta_title' => ['required', 'string', 'max:200'],
            'meta_description' => ['nullable', 'string'],
            'tags' => ['nullable', 'max:190'],
            'description' => ['nullable', 'string'],
        ]);

        $product->preOwnedSeo()->updateOrCreate([
            'product_id' => $product->id,
        ], [
            'title' => $request->meta_title,
            'description' => $request->meta_description,
            'tags' => $request->tags,
            'long_description' => $request->description,
        ]);

        return successResponse('Page updated successfully');
    }
}

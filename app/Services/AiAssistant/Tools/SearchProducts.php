<?php

namespace App\Services\AiAssistant\Tools;

use App\Models\Pos\Product\Product;
use App\Models\Pos\Product\ProductVariant;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Stringable;

class SearchProducts extends AssistantTool
{
    public function description(): Stringable|string
    {
        return 'Search products by name, SKU, category or brand. Returns each product with its category, brand and variants (SKU, stock and prices).';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'search' => $schema->string()->description('Text to match against the product name or a variant SKU.'),
            'category_id' => $schema->integer()->description('Only products in this category.'),
            'brand_id' => $schema->integer()->description('Only products of this brand.'),
            'limit' => $schema->integer()->min(1)->max(25)->description('How many products to return. Defaults to 10.'),
        ];
    }

    protected function permission(): ?string
    {
        return 'view_products';
    }

    protected function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'category_id' => ['nullable', 'integer'],
            'brand_id' => ['nullable', 'integer'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:25'],
        ];
    }

    protected function run(array $arguments): array
    {
        $search = $arguments['search'] ?? null;

        $products = Product::query()
            ->with(['category:id,name', 'brand:id,name', 'variants:id,product_id,sku,stock', 'variants.price'])
            ->when($search, fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->where('product_name', 'like', "%{$search}%")
                ->orWhereHas('variants', fn (Builder $query) => $query->where('sku', 'like', "%{$search}%"))))
            ->when($arguments['category_id'] ?? null, fn (Builder $query, mixed $categoryId) => $query->where('category_id', $categoryId))
            ->when($arguments['brand_id'] ?? null, fn (Builder $query, mixed $brandId) => $query->where('brand_id', $brandId))
            ->orderByDesc('id')
            ->limit($arguments['limit'] ?? 10)
            ->get();

        return [
            'count' => $products->count(),
            'products' => $products->map(fn (Product $product): array => [
                'id' => $product->id,
                'name' => $product->product_name,
                'category' => $product->category?->name,
                'brand' => $product->brand?->name,
                'status' => $product->status,
                'variants' => $product->variants->map(fn (ProductVariant $variant): array => [
                    'id' => $variant->id,
                    'sku' => $variant->sku,
                    'in_stock' => (bool) $variant->stock,
                    'max_retail_price' => $variant->price?->max_retail_price,
                    'retail_price' => $variant->price?->retail_price,
                ])->all(),
            ])->all(),
        ];
    }
}

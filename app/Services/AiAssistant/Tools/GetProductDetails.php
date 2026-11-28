<?php

namespace App\Services\AiAssistant\Tools;

use App\Models\Pos\Product\Product;
use App\Models\Pos\Product\ProductImage;
use App\Models\Pos\Product\ProductVariant;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Stringable;

class GetProductDetails extends AssistantTool
{
    public function description(): Stringable|string
    {
        return 'Get one product in full: descriptions, category, brand, every variant with its attribute values and prices, and its images.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'product_id' => $schema->integer()->required()->description('The product ID, from search_products.'),
        ];
    }

    protected function permission(): ?string
    {
        return 'view_products';
    }

    protected function rules(): array
    {
        return [
            'product_id' => ['required', 'integer'],
        ];
    }

    protected function run(array $arguments): array
    {
        $product = Product::query()
            ->with([
                'category:id,name',
                'brand:id,name',
                'variants.price',
                'variants.attributeValues.attribute:id,name',
                'productImages',
            ])
            ->find($arguments['product_id']);

        if (! $product) {
            return ['error' => 'not_found', 'message' => "There is no product with ID {$arguments['product_id']}."];
        }

        return [
            'id' => $product->id,
            'name' => $product->product_name,
            'slug' => $product->slug,
            'headline' => $product->headline,
            'short_description' => $product->short_description,
            'status' => $product->status,
            'category' => $product->category?->only(['id', 'name']),
            'brand' => $product->brand?->only(['id', 'name']),
            'thumbnail_image' => $product->product_image,
            'variants' => $product->variants->map(fn (ProductVariant $variant): array => [
                'id' => $variant->id,
                'sku' => $variant->sku,
                'in_stock' => (bool) $variant->stock,
                'attributes' => $variant->attributeValues
                    ->map(fn ($value): string => ($value->attribute->name ?? 'Attribute').': '.$value->value)
                    ->all(),
                'prices' => $variant->price?->only([
                    'max_retail_price', 'retail_price', 'dealer_price', 'b2b_price', 'discount_percent', 'discount_fixed',
                ]),
            ])->all(),
            'images' => $product->productImages
                ->map(fn (ProductImage $image): array => $image->only(['image_path', 'attribute_value_id', 'is_primary']))
                ->all(),
        ];
    }
}

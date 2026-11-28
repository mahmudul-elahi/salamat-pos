<?php

namespace App\Services\AiAssistant\Tools;

use App\Http\Requests\Pos\Product\ProductRequest;
use App\Models\Attribute\AttributeValue;
use App\Models\Landlord\Brand\Brand;
use App\Models\Landlord\Category\Category;
use App\Models\Pos\Product\Product;
use App\Services\Product\ProductService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Stringable;

class CreateProduct extends ApprovableAssistantTool
{
    public function __construct(protected ProductService $productService) {}

    public function description(): Stringable|string
    {
        return <<<'TEXT'
        Create a product with its variants, the same way the product form does. The user must approve this before it runs.
        Before calling it: get category_id from search_categories, brand_id from search_brands, and attribute value IDs from list_attributes.
        Every product needs at least one variant, and every variant needs at least one attribute value ID, a unique SKU and a max_retail_price.
        A variant is one combination of attribute values, for example [Red, 128GB]; list one variant per combination the user wants.
        Images must be URLs returned by import_image_from_url. Use color_images to attach images to a colour value.
        TEXT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->required()->description('The product name.'),
            'category_id' => $schema->integer()->required(),
            'brand_id' => $schema->integer()->required(),
            'headline' => $schema->string(),
            'short_description' => $schema->string(),
            'long_description' => $schema->string()->description('HTML is allowed.'),
            'tag' => $schema->string(),
            'warranty_text' => $schema->string(),
            'service_warranty' => $schema->string(),
            'thumbnail_image' => $schema->string()->description('The main image URL, from import_image_from_url.'),
            'variants' => $schema->array()->min(1)->required()->items($schema->object([
                'attribute_value_ids' => $schema->array()->items($schema->integer())->min(1)->required(),
                'sku' => $schema->string()->required(),
                'max_retail_price' => $schema->number()->required(),
                'retail_price' => $schema->number(),
                'dealer_price' => $schema->number(),
                'b2b_price' => $schema->number(),
                'discount_percent' => $schema->number(),
                'discount_fixed' => $schema->number(),
                'in_stock' => $schema->boolean()->description('Defaults to true.'),
            ])),
            'color_images' => $schema->array()->items($schema->object([
                'color_value_id' => $schema->integer()->required()->description('The attribute value ID of the colour.'),
                'featured_image' => $schema->string()->description('An image URL from import_image_from_url.'),
                'slider_images' => $schema->array()->items($schema->string()),
            ])),
        ];
    }

    protected function permission(): ?string
    {
        return 'create_products';
    }

    /**
     * Turn the tool's arguments into the payload the product form posts.
     */
    protected function prepareArguments(array $arguments): array
    {
        $variants = collect($arguments['variants'] ?? [])->filter(fn (mixed $variant): bool => is_array($variant))->values();

        $attributeValueIds = $variants->pluck('attribute_value_ids')->flatten()->filter(fn (mixed $id): bool => is_numeric($id))->unique();

        $attributes = AttributeValue::query()
            ->whereKey($attributeValueIds->all())
            ->get(['id', 'attribute_id'])
            ->groupBy('attribute_id')
            ->map(fn ($values, $attributeId): array => [
                'attribute_id' => $attributeId,
                'attribute_value_id' => $values->pluck('id')->all(),
            ])
            ->values()
            ->all();

        return [
            ...collect($arguments)->only([
                'name', 'category_id', 'brand_id', 'headline', 'short_description', 'long_description',
                'tag', 'warranty_text', 'service_warranty', 'thumbnail_image',
            ])->all(),
            'attributes' => $attributes,
            'variants' => $variants->map(fn (array $variant): array => [
                'attribute_values' => $variant['attribute_value_ids'] ?? [],
                'sku' => $variant['sku'] ?? null,
                'max_retail_price' => $variant['max_retail_price'] ?? null,
                'retail_price' => $variant['retail_price'] ?? null,
                'dealer_price' => $variant['dealer_price'] ?? null,
                'b2b_price' => $variant['b2b_price'] ?? null,
                'discount_percent' => $variant['discount_percent'] ?? null,
                'discount_fixed' => $variant['discount_fixed'] ?? null,
                'stock' => (int) ($variant['in_stock'] ?? true),
                'selected' => 1,
            ])->all(),
            'color_images' => collect($arguments['color_images'] ?? [])
                ->filter(fn (mixed $colorImage): bool => is_array($colorImage))
                ->map(fn (array $colorImage): array => [
                    'color_id' => $colorImage['color_value_id'] ?? null,
                    'featured_image' => $colorImage['featured_image'] ?? null,
                    'slider_images' => $colorImage['slider_images'] ?? [],
                ])
                ->values()
                ->all(),
            'video_links' => [],
            'seos' => [],
        ];
    }

    /**
     * The product form's rules, plus the checks the form does in the browser.
     */
    protected function rules(): array
    {
        return [
            ...(new ProductRequest)->rules(),
            'variants' => ['required', 'array', 'min:1', 'max:100'],
            'variants.*.attribute_values' => ['required', 'array', 'min:1'],
            'variants.*.attribute_values.*' => ['integer', 'exists:attribute_values,id'],
            'variants.*.sku' => [
                'required', 'string', 'max:255', 'distinct',
                Rule::unique('product_variants', 'sku')->whereNull('deleted_at'),
            ],
            'variants.*.max_retail_price' => ['required', 'numeric', 'min:0'],
            'color_images' => ['array'],
            'color_images.*.color_id' => ['required', 'integer', 'exists:attribute_values,id'],
            'video_links' => ['array'],
            'seos' => ['array'],
        ];
    }

    protected function approvalSummary(array $arguments): string
    {
        $category = Category::query()->whereKey($arguments['category_id'])->value('name');
        $brand = Brand::query()->whereKey($arguments['brand_id'])->value('name');
        $skus = collect($arguments['variants'])->pluck('sku')->implode(', ');
        $count = count($arguments['variants']);

        return "Create the product \"{$arguments['name']}\" ({$brand}, in {$category}) with {$count} ".str('variant')->plural($count).": {$skus}.";
    }

    protected function run(array $arguments): array
    {
        DB::transaction(fn () => $this->productService->createWithDetails(new Request($arguments)));

        /** @var Product $product */
        $product = $this->productService->getModel();

        return [
            'created' => true,
            'product' => [
                'id' => $product->id,
                'name' => $product->product_name,
                'slug' => $product->slug,
                'variants' => $product->variants()->pluck('sku')->all(),
            ],
        ];
    }
}

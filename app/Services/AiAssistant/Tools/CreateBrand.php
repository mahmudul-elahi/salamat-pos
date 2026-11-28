<?php

namespace App\Services\AiAssistant\Tools;

use App\Http\Requests\Landlord\Brand\BrandRequest;
use App\Models\Landlord\Brand\Brand;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Str;
use Stringable;

class CreateBrand extends ApprovableAssistantTool
{
    public function description(): Stringable|string
    {
        return 'Create a brand. Search brands first so you do not create a duplicate. The user must approve this before it runs.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->required()->description('The brand name.'),
            'tagline' => $schema->string()->description('The brand tagline.'),
            'description' => $schema->string()->description('A short description of the brand.'),
            'primary_logo' => $schema->string()->description('A logo image URL from the media library.'),
        ];
    }

    protected function permission(): ?string
    {
        return 'create_brands';
    }

    protected function rules(): array
    {
        return (new BrandRequest)->rules();
    }

    protected function approvalSummary(array $arguments): string
    {
        return "Create the brand \"{$arguments['name']}\".";
    }

    /**
     * Mirrors BrandController::store.
     */
    protected function run(array $arguments): array
    {
        $brand = Brand::query()->create(array_merge($arguments, [
            'slug' => Str::slug($arguments['name']),
            'order' => (int) Brand::query()->max('order') + 1,
        ]));

        return [
            'created' => true,
            'brand' => $brand->only(['id', 'name', 'slug']),
        ];
    }
}

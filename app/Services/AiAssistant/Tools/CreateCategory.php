<?php

namespace App\Services\AiAssistant\Tools;

use App\Http\Requests\Landlord\Category\CategoryRequest;
use App\Models\Landlord\Category\Category;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Str;
use Stringable;

class CreateCategory extends ApprovableAssistantTool
{
    public function description(): Stringable|string
    {
        return 'Create a product category, optionally as a sub-category of a parent. Search categories first so you do not create a duplicate. The user must approve this before it runs.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->required()->description('The category name.'),
            'parent_id' => $schema->integer()->description('The parent category ID, to create a sub-category.'),
            'description' => $schema->string()->description('A short description of the category.'),
            'logo' => $schema->string()->description('A logo image URL from the media library.'),
            'banner' => $schema->string()->description('A banner image URL from the media library.'),
        ];
    }

    protected function permission(): ?string
    {
        return 'create_categories';
    }

    protected function rules(): array
    {
        return (new CategoryRequest)->rules();
    }

    protected function approvalSummary(array $arguments): string
    {
        $parent = empty($arguments['parent_id']) ? null : Category::query()->whereKey($arguments['parent_id'])->value('name');

        return $parent
            ? "Create the category \"{$arguments['name']}\" under \"{$parent}\"."
            : "Create the category \"{$arguments['name']}\".";
    }

    /**
     * Mirrors CategoryController::store.
     */
    protected function run(array $arguments): array
    {
        $arguments['slug'] = Str::slug($arguments['name']);
        $arguments['parent_id'] = ($arguments['parent_id'] ?? null) ?: null;
        $arguments['order'] = (int) Category::query()
            ->when(
                empty($arguments['parent_id']),
                fn (Builder $query) => $query->whereNull('parent_id'),
                fn (Builder $query) => $query->where('parent_id', $arguments['parent_id']),
            )
            ->max('order') + 1;

        $category = Category::query()->create($arguments);

        return [
            'created' => true,
            'category' => $category->only(['id', 'name', 'parent_id', 'slug']),
        ];
    }
}

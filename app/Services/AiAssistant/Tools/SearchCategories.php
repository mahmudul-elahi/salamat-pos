<?php

namespace App\Services\AiAssistant\Tools;

use App\Models\Landlord\Category\Category;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Stringable;

/**
 * Category lookups are open to every signed-in user, like the existing category picker.
 */
class SearchCategories extends AssistantTool
{
    public function description(): Stringable|string
    {
        return 'Find product categories by name. Returns each category with its ID and parent category. Use it to get a category_id before creating a product or a sub-category.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'search' => $schema->string()->nullable()->description('Text to match against the category name. Leave empty to list categories.'),
        ];
    }

    protected function permission(): ?string
    {
        return null;
    }

    protected function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function run(array $arguments): array
    {
        $search = $arguments['search'] ?? null;

        $categories = Category::query()
            ->with('parent:id,name')
            ->when($search, fn (Builder $query) => $query->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->limit(50)
            ->get(['id', 'name', 'parent_id', 'status']);

        return [
            'categories' => $categories->map(fn (Category $category): array => [
                'id' => $category->id,
                'name' => $category->name,
                'parent' => $category->parent?->name,
                'status' => $category->status,
            ])->all(),
        ];
    }
}

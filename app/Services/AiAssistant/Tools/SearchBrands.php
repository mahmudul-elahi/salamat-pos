<?php

namespace App\Services\AiAssistant\Tools;

use App\Models\Landlord\Brand\Brand;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Stringable;

/**
 * Brand lookups are open to every signed-in user, like the existing brand picker.
 */
class SearchBrands extends AssistantTool
{
    public function description(): Stringable|string
    {
        return 'Find brands by name. Returns each brand with its ID. Use it to get a brand_id before creating a product.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'search' => $schema->string()->description('Text to match against the brand name. Leave empty to list brands.'),
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

        return [
            'brands' => Brand::query()
                ->when($search, fn (Builder $query) => $query->where('name', 'like', "%{$search}%"))
                ->orderBy('name')
                ->limit(50)
                ->get(['id', 'name'])
                ->toArray(),
        ];
    }
}

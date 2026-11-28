<?php

namespace App\Services\AiAssistant\Tools;

use App\Models\Attribute\Attribute;
use App\Models\Attribute\AttributeValue;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Stringable;

/**
 * Attribute lookups are open to every signed-in user, like the existing attribute picker.
 *
 * Stores can have hundreds of attribute values, so the tool answers in two steps: without an
 * attribute it lists the attributes and how many values each has; with one it lists that
 * attribute's values, optionally filtered by search text.
 */
class ListAttributes extends AssistantTool
{
    private const int MAX_VALUES = 60;

    public function description(): Stringable|string
    {
        return 'List variant attributes (such as Color, Storage or Size). Call it with no arguments to get every attribute with its ID and number of values. Call it with attribute_id (and optionally search) to get that attribute\'s values and their IDs. Variants are built from these attribute value IDs.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'attribute_id' => $schema->integer()->nullable()->description('List the values of this attribute.'),
            'search' => $schema->string()->nullable()->description('Only values containing this text, such as "black" or "128".'),
        ];
    }

    protected function permission(): ?string
    {
        return null;
    }

    protected function rules(): array
    {
        return [
            'attribute_id' => ['nullable', 'integer'],
            'search' => ['nullable', 'string', 'max:100'],
        ];
    }

    protected function run(array $arguments): array
    {
        if (empty($arguments['attribute_id'])) {
            return [
                'attributes' => Attribute::query()
                    ->withCount('values')
                    ->orderBy('name')
                    ->get(['id', 'name'])
                    ->map(fn (Attribute $attribute): array => [
                        'id' => $attribute->id,
                        'name' => $attribute->name,
                        'value_count' => $attribute->values_count,
                    ])
                    ->all(),
            ];
        }

        $attribute = Attribute::query()->find($arguments['attribute_id'], ['id', 'name']);

        if (! $attribute) {
            return ['error' => 'not_found', 'message' => "There is no attribute with ID {$arguments['attribute_id']}."];
        }

        $search = $arguments['search'] ?? null;

        $values = $attribute->values()
            ->when($search, fn (Builder $query) => $query->where('value', 'like', "%{$search}%"))
            ->orderBy('value')
            ->limit(self::MAX_VALUES + 1)
            ->get(['id', 'value']);

        return [
            'attribute' => ['id' => $attribute->id, 'name' => $attribute->name],
            'values' => $values->take(self::MAX_VALUES)
                ->map(fn (AttributeValue $value): array => ['id' => $value->id, 'value' => $value->value])
                ->all(),
            'more_available' => $values->count() > self::MAX_VALUES,
        ];
    }
}

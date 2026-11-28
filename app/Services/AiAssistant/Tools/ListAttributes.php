<?php

namespace App\Services\AiAssistant\Tools;

use App\Models\Attribute\Attribute;
use App\Models\Attribute\AttributeValue;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Stringable;

/**
 * Attribute lookups are open to every signed-in user, like the existing attribute picker.
 */
class ListAttributes extends AssistantTool
{
    public function description(): Stringable|string
    {
        return 'List variant attributes (such as Color, Storage or Size) with every value and its ID. Variants are built from these attribute value IDs.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    protected function permission(): ?string
    {
        return null;
    }

    protected function run(array $arguments): array
    {
        return [
            'attributes' => Attribute::query()
                ->with('values:id,attribute_id,value')
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Attribute $attribute): array => [
                    'id' => $attribute->id,
                    'name' => $attribute->name,
                    'values' => $attribute->values
                        ->map(fn (AttributeValue $value): array => ['id' => $value->id, 'value' => $value->value])
                        ->all(),
                ])
                ->all(),
        ];
    }
}

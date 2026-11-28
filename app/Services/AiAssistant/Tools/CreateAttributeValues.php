<?php

namespace App\Services\AiAssistant\Tools;

use App\Models\Attribute\Attribute;
use App\Models\Attribute\AttributeValue;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Stringable;

class CreateAttributeValues extends ApprovableAssistantTool
{
    public function description(): Stringable|string
    {
        return 'Add values to a variant attribute (for example the colour "Midnight Blue" to "Color"), creating the attribute itself if it does not exist yet. Values that already exist are kept as they are. Call list_attributes first. The user must approve this before it runs.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'attribute_name' => $schema->string()->required()->description('The attribute name, such as Color, Storage or Size.'),
            'values' => $schema->array()->items($schema->string())->min(1)->required()->description('The values to add, such as ["Red", "Blue"].'),
        ];
    }

    protected function permission(): ?string
    {
        return 'create_attributes';
    }

    protected function rules(): array
    {
        return [
            'attribute_name' => ['required', 'string', 'max:255'],
            'values' => ['required', 'array', 'min:1', 'max:50'],
            'values.*' => ['required', 'string', 'max:255', 'distinct:ignore_case'],
        ];
    }

    protected function approvalSummary(array $arguments): string
    {
        return 'Add '.implode(', ', $arguments['values'])." to the attribute \"{$arguments['attribute_name']}\".";
    }

    protected function run(array $arguments): array
    {
        return DB::transaction(function () use ($arguments): array {
            $attribute = Attribute::query()->firstOrCreate(['name' => $arguments['attribute_name']], ['status' => 1]);

            foreach ($arguments['values'] as $value) {
                $attribute->values()->firstOrCreate(['value' => $value], ['status' => 1]);
            }

            return [
                'created' => true,
                'attribute' => [
                    'id' => $attribute->id,
                    'name' => $attribute->name,
                    'values' => $attribute->values()->get(['id', 'value'])
                        ->map(fn (AttributeValue $value): array => ['id' => $value->id, 'value' => $value->value])
                        ->all(),
                ],
            ];
        });
    }
}

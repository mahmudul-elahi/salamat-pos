<?php

declare(strict_types=1);

namespace App\Http\Requests\Pos\AiAssistant;

use Illuminate\Foundation\Http\FormRequest;

class AiAssistantApprovalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'decisions' => ['required', 'array', 'min:1'],
            'decisions.*.id' => ['required', 'string', 'distinct'],
            'decisions.*.approved' => ['required', 'boolean'],
        ];
    }

    /**
     * The decisions keyed by tool call ID.
     *
     * @return array<string, bool>
     */
    public function decisionsById(): array
    {
        return collect($this->validated('decisions'))
            ->mapWithKeys(fn (array $decision): array => [$decision['id'] => (bool) $decision['approved']])
            ->all();
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Requests\Pos\AiAssistant;

use Illuminate\Foundation\Http\FormRequest;

class AiAssistantMessageRequest extends FormRequest
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
            'message' => ['required', 'string', 'max:4000'],
            'conversation_id' => ['nullable', 'string', 'uuid'],
        ];
    }
}

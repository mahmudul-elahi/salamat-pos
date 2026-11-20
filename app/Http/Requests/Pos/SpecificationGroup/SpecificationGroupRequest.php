<?php

declare(strict_types=1);

namespace App\Http\Requests\Pos\SpecificationGroup;

use Illuminate\Foundation\Http\FormRequest;

class SpecificationGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'parent_id' => ['nullable', 'integer', 'exists:specification_groups,id'],
            'values' => ['required', 'array', 'min:1'],
            'values.*' => ['required', 'string', 'max:255'],
        ];
    }
}

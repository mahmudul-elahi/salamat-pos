<?php

namespace App\Http\Requests\Landlord\Attribute;

use Illuminate\Foundation\Http\FormRequest;

class AttributeRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'values' => ['required', 'array'],
            'values.*' => ['required', 'string', 'max:255'],
        ];
    }
    public function attributes(): array
    {
        return [
            'values' => 'values',
            'values.*' => 'value',
        ];
    }
}

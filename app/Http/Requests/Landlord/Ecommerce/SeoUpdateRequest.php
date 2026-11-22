<?php

declare(strict_types=1);

namespace App\Http\Requests\Landlord\Ecommerce;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class SeoUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:191'],
            'slug' => ['required', 'string', 'max:191'],
            'description' => ['nullable', 'string'],
            'meta_title' => ['nullable', 'string', 'max:191'],
            'meta_description' => ['nullable', 'string'],
            'tags' => ['nullable'],
        ];
    }

    public function attributes(): array
    {
        return [
            'meta_title' => 'meta title',
            'meta_description' => 'meta description',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'The title field is required.',
            'slug.required' => 'The slug field is required.',
        ];
    }
}

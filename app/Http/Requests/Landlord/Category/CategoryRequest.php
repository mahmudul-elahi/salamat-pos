<?php

namespace App\Http\Requests\Landlord\Category;

use Illuminate\Foundation\Http\FormRequest;

class CategoryRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'parent_id' => [
                'nullable',
                'exists:categories,id',
                function ($attribute, $value, $fail) {
                    $category = $this->route('category');
                    if ($value && $category && (int) $value === (int) $category->id) {
                        $fail('A category cannot be its own parent.');
                    }
                },
            ],
            'description' => 'nullable|string',
            'logo' => 'nullable|string',
            'secondary_logo' => 'nullable|string',
            'banner' => 'nullable|string',
            'info_banner' => 'nullable|string',
        ];
    }
}

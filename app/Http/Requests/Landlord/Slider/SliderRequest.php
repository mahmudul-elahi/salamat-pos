<?php

declare(strict_types=1);

namespace App\Http\Requests\Landlord\Slider;

use Illuminate\Foundation\Http\FormRequest;

class SliderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'exists:tenants,id'],
            'status' => ['required'],
            'heading' => ['nullable', 'string', 'max:255'],
            'paragraph' => ['nullable', 'string', 'max:255'],
            'short_para' => ['nullable', 'string', 'max:255'],
            'url' => ['nullable', 'string', 'max:255'],
            'numbering' => ['nullable', 'integer', 'unique:sliders'],
            'button_type' => ['nullable'],
            'banner_feature' => ['nullable'],
            'image' => ['required'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'The slider type field is required',
        ];
    }
}

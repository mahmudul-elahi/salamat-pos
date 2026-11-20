<?php

namespace App\Http\Requests\Landlord\Brand;

use Illuminate\Foundation\Http\FormRequest;

class BrandRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'tagline' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'android_app' => ['nullable', 'string', 'max:255'],
            'ios_app' => ['nullable', 'string', 'max:255'],
            'tags' => ['nullable', 'max:255'],
            'primary_logo' => ['nullable', 'string'],
            'shop_banner' => ['nullable', 'string'],
            'secondary_logo' => ['nullable', 'string'],
            'info_banner' => ['nullable', 'string'],

        ];
    }
}

<?php

namespace App\Http\Requests\Landlord\Dealer;

use Illuminate\Foundation\Http\FormRequest;

class DealerRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'business_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'max:100', 'unique:suppliers,email'],
            'primary_phone' => ['required', 'max:20', 'unique:suppliers,phone'],
            'secondary_phone' => ['nullable', 'max:20'],
            'trade_license_number' => ['nullable', 'max:255'],
            'business_logo' => ['nullable', 'url'],
            // 'image' => 'nullable|mimes:jpeg,jpg,png|max:2048',
            'area_id' => ['nullable', 'numeric', 'exists:areas,id'],
            'street_address' => ['required', 'max:100'],
        ];
    }

    public function attributes(): array
    {
        return [
            'country_id' => 'country',
        ];
    }
}

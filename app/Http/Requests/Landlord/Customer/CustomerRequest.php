<?php

namespace App\Http\Requests\Landlord\Customer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustomerRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'name'             => ['required', 'string', 'max:255'],
            'business_name'    => ['nullable', 'string', 'max:255'],
            'email'            => ['nullable', 'email', 'max:100', Rule::unique('contacts')->ignore($this->customer)->whereNull('deleted_at')],
            'phone'            => ['required', 'max:20', Rule::unique('contacts')->ignore($this->customer)->whereNull('deleted_at')],
            'secondary_phone'  => ['nullable', 'max:20'],
            'country_id'       => ['nullable', 'numeric', 'exists:countries,id'],
            'new_district_id'  => ['nullable', 'numeric', 'exists:new_districts,id'],
            'area_id'          => ['nullable', 'numeric', 'exists:areas,id'],
            'thana'            => ['nullable', 'string', 'max:255'],
            'postal_code'      => ['nullable', 'string', 'max:20'],
            'address'          => ['nullable', 'max:500'],
            'profile_picture'  => ['nullable', 'url', 'max:255'],
            'business_logo'    => ['nullable', 'url', 'max:255'],
        ];
    }
}

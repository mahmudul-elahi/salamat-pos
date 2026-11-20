<?php

namespace App\Http\Requests\Landlord\Supplier;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function supplierId(): ?int
    {
        return $this->route('supplier')?->id
            ?? $this->route('supplier');
    }

    public function rules(): array
    {
        $supplierId = $this->supplierId();
        $user = auth()->user();

        return [

            // 'tenant_id' => [
            //     Rule::requiredIf($user?->isAppAdmin()),
            //     'nullable',
            //     'exists:tenants,id',
            // ],

            'name' => ['required', 'string', 'max:255'],
            'business_name' => ['required', 'string', 'max:255'],

            'email' => [
                'nullable',
                'email',
                'max:100',
                Rule::unique('contacts', 'email')->ignore($supplierId)->whereNull('deleted_at'),
            ],

            'phone' => [
                'required',
                'string',
                'max:20',
                Rule::unique('contacts', 'phone')->ignore($supplierId)->whereNull('deleted_at'),
            ],

            'secondary_phone' => ['nullable', 'string', 'max:20'],

            'business_logo' => ['nullable', 'url', 'max:255'],
            'profile_picture' => ['nullable', 'url', 'max:255'],

            'country_id' => ['required', 'integer', 'exists:countries,id'],
            'new_district_id' => ['nullable', 'integer', 'exists:new_districts,id'],
            'area_id' => ['nullable', 'integer', 'exists:areas,id'],

            'address' => ['required', 'string', 'max:255'],
        ];
    }
}

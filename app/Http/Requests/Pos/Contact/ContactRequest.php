<?php

namespace App\Http\Requests\Pos\Contact;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ContactRequest extends FormRequest
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
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        $id = optional($this->contact)->id;
        return [
            'type' => ['required', 'in:customer,supplier'],
            'tenant_id' => ['nullable','required_if:type,supplier', 'exists:tenants,id'],
            'name' => ['required', 'string'],
            'email' => ['nullable', 'max:100', Rule::unique('contacts', 'email')->ignore($id)->whereNull('deleted_at')],
            'phone' => ['required', 'max:20', Rule::unique('contacts', 'phone')->ignore($id)->whereNull('deleted_at')],
            'secondary_phone' => ['nullable', 'max:20'],
            'country_id' => ['nullable','required_if:type,supplier', 'numeric', 'exists:countries,id'],
            'new_district_id' => ['nullable', 'numeric', 'exists:new_districts,id'],
            'area_id' => ['nullable', 'numeric', 'exists:areas,id'],
            'address' => ['required_if:type,supplier', 'max:190'],
        ];
    }
}

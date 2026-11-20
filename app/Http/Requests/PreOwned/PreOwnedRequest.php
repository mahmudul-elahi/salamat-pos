<?php

namespace App\Http\Requests\PreOwned;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PreOwnedRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $productId = $this->route('pre_owned_product') ?? null;


        return [
            'product_id' => 'required|exists:products,id',
            'product_variant_id' => 'required|exists:product_variants,id',
            'price' => 'required|numeric',

            'serial_number' => [
                'required',
                'string',
                Rule::unique('pre_owneds', 'serial_number')->ignore($productId),
            ],

            'booking_amount' => ['nullable', 'numeric', 'min:0'],
            'purchase_point' => ['nullable', 'numeric', 'min:0'],

            'battery_health' => ['nullable', 'string', 'max:190'],
            'discount_type' => ['nullable', 'string', 'in:fixed,percent'],
            'discount_amount' => [
                'nullable',
                'numeric',
                'required_with:discount_type'
            ],

            'box' => ['nullable', 'boolean'],
            'charging_time' => ['nullable', 'string', 'max:190'],
            'official_service_warranty' => ['nullable', 'string', 'max:190'],

            'variant_attributes' => 'required|array|min:1',
            'variant_attributes.*.attribute_id' => 'required|exists:attributes,id',
            'variant_attributes.*.attribute_value_id' => 'required|exists:attribute_values,id',

            'thumbnail_image' => 'nullable|string',
            'description' => 'nullable|string',

            'attachments' => 'nullable|array',
            'attachments.*' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'variant_attributes.required' => 'At least one variant attribute is required.',
            'variant_attributes.*.attribute_id.required' => 'The attribute field is required.',
            'variant_attributes.*.attribute_id.exists' => 'The selected attribute is invalid.',
            'variant_attributes.*.attribute_value_id.required' => 'The attribute value field is required.',
            'variant_attributes.*.attribute_value_id.exists' => 'The selected attribute value is invalid.',
        ];
    }
}

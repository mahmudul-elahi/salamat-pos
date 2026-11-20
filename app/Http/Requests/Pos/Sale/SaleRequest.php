<?php

namespace App\Http\Requests\Pos\Sale;

use App\Http\Requests\CustomRule\MaxDigitsPrecision;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaleRequest extends FormRequest
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
        $isAppAdmin = auth()->user()->isAppAdmin();

        return [
            'tenant_id' => [
                Rule::requiredIf($isAppAdmin),
                'exists:tenants,id',
            ],
            'contact_id' => ['required', 'exists:contacts,id'],
            'seller_id' => ['nullable', 'exists:users,id'],
            'address' => ['nullable', 'string', 'max:1000'],
            'date' => ['required', 'date'],
            'sub_total' => ['required', 'numeric', new MaxDigitsPrecision(16)],
            'order_tax' => ['nullable', 'numeric', new MaxDigitsPrecision(16)],
            'shipping_cost' => ['nullable', 'numeric', new MaxDigitsPrecision(16)],
            'grand_total' => ['required', 'numeric', new MaxDigitsPrecision(16)],
            'items' => ['required', 'array', 'min:1'],
            'items.*.serial_number' => ['required', 'string'],
            'items.*.sale_type' => ['nullable', 'in:product,exchange'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.variant_id' => ['required', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', new MaxDigitsPrecision(16)],
            'items.*.discount_type' => ['nullable', 'in:fixed,percent'],
            'items.*.discount_amount' => ['nullable', 'numeric', new MaxDigitsPrecision(16)],
            'items.*.note' => ['nullable', 'string', 'max:500'],
            'additional_charge_name' => ['nullable', 'string', 'max:500'],
            'additional_charge' => ['nullable', 'numeric', 'min:0', new MaxDigitsPrecision(16)],

        ];
    }

    public function attributes(): array
    {
        return [
            'customer_id' => 'customer',
            'tenant_id' => 'shop',
        ];
    }

    public function messages(): array
    {
        return [
            'items.*.serial_number.required' => 'The serial number is required',
            'items.*.product_id.required' => 'The product is required',
            'items.*.variant_id.required' => 'The variant is required',
            'items.*.quantity.required' => 'The quantity is required',
            'items.*.unit_price.required' => 'The unit price is required',
        ];
    }
}

<?php

namespace App\Http\Requests\Pos\SaleReturn;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Http\Requests\CustomRule\MaxDigitsPrecision;

class SaleReturnRequest extends FormRequest
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
        return [
            'contact_id' => ['required', 'exists:contacts,id'],
            'date' => ['required', 'date'],
            'sub_total' => ['required', 'numeric', new MaxDigitsPrecision(16)],
            'order_tax' => ['nullable', 'numeric', new MaxDigitsPrecision(16)],
            'shipping_cost' => ['nullable', 'numeric', new MaxDigitsPrecision(16)],
            'grand_total' => ['required', 'numeric', new MaxDigitsPrecision(16)],
            'items' => ['required', 'array', 'min:1'],
            'items.*.serial_number' => ['required', 'exists:product_serials,serial_number'],
            'items.*.sale_id' => ['required', 'exists:sales,id'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.variant_id' => ['required', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.discount_type' => ['nullable', 'in:fixed,percent'],
            'items.*.discount_amount' => ['nullable', 'numeric', new MaxDigitsPrecision(16)],
            'items.*.unit_price' => ['required', 'numeric', new MaxDigitsPrecision(16)],
            'items.*.note' => ['nullable', 'string', 'max:500'],
            'return_amount' => ['nullable', 'numeric', new MaxDigitsPrecision(16)],
            'payment_method_id' => ['nullable', 'exists:payment_methods,id'],

        ];
    }

    public function attributes(): array
    {
        return [
            'customer_id' => 'customer',
            'tenant_id' => 'shop'
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

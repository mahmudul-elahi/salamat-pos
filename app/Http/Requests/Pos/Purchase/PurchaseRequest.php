<?php

namespace App\Http\Requests\Pos\Purchase;

use App\Http\Requests\CustomRule\MaxDigitsPrecision;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isAppAdmin = auth()->user()->isAppAdmin();

        return [
            'tenant_id' => [
                Rule::requiredIf($isAppAdmin),
                'exists:tenants,id',
            ],
            'contact_id' => ['nullable', 'exists:contacts,id'],
            'date' => ['required', 'date'],
            'invoice_bill' => ['nullable', 'file', 'max:2048', 'mimes:jpeg,jpg,png,pdf,svg'],
            'sub_total' => ['required', 'numeric', new MaxDigitsPrecision(12)],
            'discount_type' => ['nullable', 'in:fixed,percent'],
            'discount_amount' => ['nullable', 'numeric', new MaxDigitsPrecision(12)],
            'order_tax' => ['nullable', 'numeric', new MaxDigitsPrecision(12)],
            'shipping_cost' => ['nullable', 'numeric', new MaxDigitsPrecision(12)],
            'grand_total' => ['required', 'numeric', new MaxDigitsPrecision(12)],

            'items' => ['required', 'array', 'min:1'],
            'items.*.serial_number' => [
                'required',
                'exists:product_serials,serial_number',
            ],

            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.variant_id' => ['required', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', new MaxDigitsPrecision(12)],
            'items.*.note' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function attributes(): array
    {
        return [
            'supplier_id' => 'supplier',
            'tenant_id' => 'shop',
        ];
    }

    public function messages(): array
    {
        return [
            'items.*.serial_number.required' => 'The serial number is required',
            'items.*.serial_number.unique' => 'The serial number is already purchased',
            'items.*.product_id.required' => 'The product is required',
            'items.*.variant_id.required' => 'The variant is required',
            'items.*.quantity.required' => 'The quantity is required',
            'items.*.unit_price.required' => 'The unit price is required',
        ];
    }
}

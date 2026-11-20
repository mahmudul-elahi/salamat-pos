<?php

declare(strict_types=1);

namespace App\Http\Requests\Pos\PurchaseReturn;

use App\Http\Requests\CustomRule\MaxDigitsPrecision;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PurchaseReturnRequest extends FormRequest
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
        $this->route('purchase_return');

        return [
            'contact_id' => ['required', 'exists:contacts,id'],
            'date' => ['required', 'date'],
            'invoice_bill' => ['nullable', 'image', 'max:2048', 'mimes:jpeg,jpg,png,pdf,svg'],
            'sub_total' => ['required', 'numeric', new MaxDigitsPrecision(16)],
            'discount_type' => ['nullable', 'in:fixed,percent'],
            'discount_amount' => ['nullable', 'numeric', new MaxDigitsPrecision(16)],
            'order_tax' => ['nullable', 'numeric', new MaxDigitsPrecision(16)],
            'shipping_cost' => ['nullable', 'numeric', new MaxDigitsPrecision(16)],
            'grand_total' => ['required', 'numeric', new MaxDigitsPrecision(16)],
            'items' => ['required', 'array', 'min:1'],
            // 'items.*.serial_number' => ['required', 'exists:product_serials,serial_number', 'unique:purchase_return_items,serial_number'],
            'items.*.serial_number' => [
                'required',
                'exists:product_serials,serial_number',
                //                Rule::unique('purchase_return_items', 'serial_number')
                //                    ->ignore($purchaseReturnId, 'purchase_return_id'),

            ],
            'items.*.purchase_id' => ['required', 'exists:purchases,id'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.variant_id' => ['required', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', new MaxDigitsPrecision(16)],
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
            'items.*.serial_number.unique' => 'The serial number is already return',
            'items.*.product_id.required' => 'The product is required',
            'items.*.variant_id.required' => 'The variant is required',
            'items.*.quantity.required' => 'The quantity is required',
            'items.*.unit_price.required' => 'The unit price is required',
        ];
    }
}

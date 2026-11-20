<?php

namespace App\Http\Requests\Pos\Product;

use App\Http\Requests\CustomRule\MaxDigitsPrecision;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductBarcodeRequest extends FormRequest
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
                'exists:tenants,id'
            ],

            'items' => ['required', 'array'],
            'items.*.serial_number' => [
                'required',
                'max:191',
                Rule::unique('product_serials', 'serial_number')
            ],
            'items.*.max_retail_price' => ['required', new MaxDigitsPrecision(16)],
            'items.*.variant_id' => ['required', Rule::exists('product_variants', 'id')],
            'items.*.product_id' => ['required', Rule::exists('products', 'id')],
        ];
    }

    public function messages(): array
    {
        return [
            'items.*.serial_number.required' => 'The serial number is required',
            'items.*.serial_number.unique' => 'The serial number is already exists',
            'items.*.max_retail_price.required' => 'The max retail price is required',
            'items.*.variant_id.required' => 'The variant is required',
            'items.*.product_id.required' => 'The product is required',

        ];
    }
}

<?php

namespace App\Http\Requests\Pos\Payment;

use App\Http\Requests\CustomRule\MaxDigitsPrecision;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class PaymentRequest extends FormRequest
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
            'date' => ['required', 'date'],
            'amount' => ['required', 'numeric', new MaxDigitsPrecision(15)],
            'payment_method_id' => ['required', 'exists:payment_methods,id'],
            'note' => ['nullable', 'string'],
        ];
    }
}

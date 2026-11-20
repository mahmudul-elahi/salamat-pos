<?php

declare(strict_types=1);

namespace App\Http\Requests\Pos\Investment;

use App\Http\Requests\CustomRule\MaxDigitsPrecision;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class InvestmentRequest extends FormRequest
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
            'tenant_id' => ['required', 'numeric', 'exists:tenants,id'],
            'title' => ['required', 'string', 'max:191'],
            'date' => ['required', 'date'],
            'investment_category_id' => ['required', 'numeric', 'exists:investment_categories,id'],
            'amount' => ['required', 'numeric', new MaxDigitsPrecision(16)],
            'note' => ['nullable', 'string'],
        ];
    }
}

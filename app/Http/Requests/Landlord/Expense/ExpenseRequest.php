<?php

declare(strict_types=1);

namespace App\Http\Requests\Landlord\Expense;

use App\Http\Requests\CustomRule\MaxDigitsPrecision;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class ExpenseRequest extends FormRequest
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
     * @return array<string, Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:191'],
            'date' => ['required', 'date'],
            //            'tenant_id' => ['required', 'numeric', 'exists:tenants,id'],
            'expense_category_id' => ['required', 'numeric', 'exists:expense_categories,id'],
            'amount' => ['required', 'numeric', new MaxDigitsPrecision(16)],
            'note' => ['nullable', 'string'],
            'payment_method_id' => ['required', 'exists:payment_methods,id'],
            'attachments.*' => request()->file('attachments') ? ['nullable', 'mimes:jpeg,png,jpg,gif,svg,pdf,zip,doc,docx,xls,xlsx', 'max:2048'] : [],
        ];
    }

    public function attributes(): array
    {
        return [
            'expense_category_id' => 'category',
        ];
    }

    public function messages(): array
    {
        return [
            'attachments.*.mimes' => 'Allowed file types: jpeg, jpg, gif, png, pdf, zip, doc, xls',
            'attachments.*.max' => 'Sorry! Maximum allowed size for an image is 2MB',
        ];
    }
}

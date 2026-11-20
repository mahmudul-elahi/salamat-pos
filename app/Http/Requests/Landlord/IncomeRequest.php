<?php

namespace App\Http\Requests\Landlord;

use App\Http\Requests\CustomRule\MaxDigitsPrecision;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class IncomeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'numeric', 'exists:tenants,id'],
//            'user_id' => ['required', 'exists:users,id'],
            'title' => ['required', 'string', 'max:191'],
            'date' => ['required', 'date'],
            'income_category_id' => ['required', 'numeric', 'exists:income_categories,id'],
            'amount' => ['required', 'numeric', new MaxDigitsPrecision(16)],
            'note' => ['nullable', 'string'],
            'attachments.*' => request()->file('attachments') ? ['nullable', 'mimes:jpeg,png,jpg,gif,svg,pdf,zip,doc,docx,xls,xlsx', 'max:2048'] : [],
        ];
    }

    public function attributes(): array
    {
        return [
            'income_category_id' => 'category',
        ];
    }
}

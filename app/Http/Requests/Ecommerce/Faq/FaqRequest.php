<?php

namespace App\Http\Requests\Ecommerce\Faq;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FaqRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id'          => ['required', Rule::exists('tenants', 'id')],
            'order'              => ['nullable', 'integer', 'min:0'],
            'status'             => ['required', 'boolean'],
            'faqs'               => ['required', 'array', 'min:1'],
            'faqs.*.id'          => ['nullable', 'integer'],
            'faqs.*.lang'        => ['required', 'string', 'in:en,bn'],
            'faqs.*.question'    => ['required', 'string'],
            'faqs.*.answer'      => ['required', 'string'],
        ];
    }
}


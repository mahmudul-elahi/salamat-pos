<?php

namespace App\Http\Requests\Ecommerce\Emi;

use Illuminate\Foundation\Http\FormRequest;

class EmiBankRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bank_name'             => 'required|string|max:255',
            'gw_key'                => 'required|string|max:100',
            'logo'                  => 'nullable|string',
            'is_active'             => 'boolean',
            'tenures'               => 'required|array|min:1',
            'tenures.*.months'      => 'required|integer|in:3,6,9,12,18,24,30,36',
            'tenures.*.charge_rate' => 'required|numeric|min:0|max:100',
            'tenures.*.is_active'   => 'boolean',
        ];
    }
}

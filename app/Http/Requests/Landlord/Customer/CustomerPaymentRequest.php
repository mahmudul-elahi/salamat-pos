<?php

namespace App\Http\Requests\Landlord\Customer;

use Illuminate\Foundation\Http\FormRequest;

class CustomerPaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        $isAdmin = auth()->user()->isAppAdmin();
        return [
            'contact_id' => 'required|exists:contacts,id',
            'tenant_id' => [
                $isAdmin ? 'required' : 'nullable',
                'exists:tenants,id'
            ],
            'amount' => 'required|numeric|min:0.01',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'date' => 'required|date',
            'note' => 'nullable|string'
        ];
    }

    public function messages(): array
    {
        return [
            'tenant_id.required' => 'the warehouse field is required'
        ];
    }
}

<?php

namespace App\Http\Requests\Landlord\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users')->ignore(auth()->id())],
            'phone' => ['required', 'max:11', 'min:11', Rule::unique('users')->ignore(auth()->id())],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'exists:roles,id'],
            'tenant_id' => ['nullable', 'exists:tenants,id'],
        ];
    }
}

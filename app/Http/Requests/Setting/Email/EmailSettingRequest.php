<?php

namespace App\Http\Requests\Setting\Email;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class EmailSettingRequest extends FormRequest
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
        // Base rules
        $rules = [
            'provider' => ['required'],
            'from_name' => ['required'],
            'from_email' => ['required', 'email'],
        ];

        // Additional rules for SMTP provider
        if (request('provider') === 'smtp') {
            $rules = array_merge($rules, [
                'smtp_host' => ['required'],
                'smtp_port' => ['required', 'numeric'],
                'smtp_username' => ['required'],
                'email_password' => ['required'],
                'encryption_type' => ['required'],
            ]);
        }

        return $rules;
    }

}

<?php

namespace App\Http\Requests\Pos\Director;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DirectorRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function director(): ?int
    {
        return $this->route('director')?->id
            ?? $this->route('director');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        $director = $this->director();

        return [
            'name' => ['required', 'string', 'max:100'],
            'title' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($director)],
            'phone' => ['required', 'string', 'max:20', Rule::unique('users', 'phone')->ignore($director)],
            'password' => ['required', 'string', 'min:8', 'max:30'],
            'profile_photo' => ['nullable', 'string'],
        ];
    }
}

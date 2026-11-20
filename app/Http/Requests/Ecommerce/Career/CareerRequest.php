<?php

declare(strict_types=1);

namespace App\Http\Requests\Ecommerce\Career;

use Illuminate\Foundation\Http\FormRequest;

class CareerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'heading' => ['required', 'string', 'max:255'],
            'vacancies' => ['required', 'integer', 'min:1'],
            'type' => ['required', 'string', 'max:100'],
            'salary' => ['nullable', 'string', 'max:100'],
            'image' => ['nullable', 'string'],
            'qualification' => ['nullable', 'string'],
            'deadline' => ['nullable', 'date'],
            'description' => ['nullable', 'string'],
            'benefits' => ['nullable', 'string'],
            'keywords' => ['nullable', 'string', 'max:255'],
        ];
    }
}

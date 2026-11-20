<?php

declare(strict_types=1);

namespace App\Http\Requests\Landlord\Ecommerce\OurTeam;

use Illuminate\Foundation\Http\FormRequest;

class OurTeamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'designation' => ['required', 'string', 'max:255'],
            'about' => ['required', 'string'],
            'profile_photo' => ['required', 'string'],
        ];
    }
}

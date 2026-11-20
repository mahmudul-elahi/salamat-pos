<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class UserBelongsToTenant implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $user = auth()->user();

        if (! $user || $user->isAppAdmin()) {
            return;
        }

        if (! $user->tenants()->whereKey($value)->exists()) {
            $fail('You do not belong to the selected shop/warehouse.');
        }
    }
}

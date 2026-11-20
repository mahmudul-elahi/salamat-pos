<?php

namespace App\Http\Requests\CustomRule;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class MaxDigitsPrecision implements ValidationRule
{
    public function __construct(private readonly int $digits) {}

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $chaLength = strlen((string) $value);

        if ($chaLength > $this->digits) {
            $fail("The {$attribute} must have a maximum of {$this->digits} digits in total.");
        }
    }
}

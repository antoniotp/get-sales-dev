<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class ValidPhoneNumber implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // E.164 format regex: starts with '+', followed by digits.
        if (! preg_match('/^\+[1-9]\d{1,14}$/', $value)) {
            $fail('El :attribute no es un número de teléfono válido.');
        }
    }
}

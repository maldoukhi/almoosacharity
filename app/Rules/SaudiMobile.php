<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * Validates a Saudi mobile number in local format: 05 followed by 8 digits.
 */
class SaudiMobile implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! preg_match('/^05\d{8}$/', (string) $value)) {
            $fail(__('validation.custom.saudi_mobile'));
        }
    }
}

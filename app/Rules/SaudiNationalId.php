<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * Validates a Saudi national ID / iqama number: 10 digits starting with
 * 1 (citizen) or 2 (resident).
 */
class SaudiNationalId implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! preg_match('/^[12]\d{9}$/', (string) $value)) {
            $fail(__('validation.custom.saudi_national_id'));
        }
    }
}

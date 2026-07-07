<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * Validates a Saudi IBAN: "SA" followed by 22 digits, and a real ISO 13616
 * (MOD-97) checksum — not just the shape of the string.
 */
class SaudiIban implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $iban = strtoupper((string) $value);

        if (! preg_match('/^SA\d{22}$/', $iban)) {
            $fail(__('validation.custom.saudi_iban.format'));

            return;
        }

        if (! $this->hasValidChecksum($iban)) {
            $fail(__('validation.custom.saudi_iban.checksum'));
        }
    }

    /**
     * ISO 13616 (MOD-97) checksum: move the first 4 characters to the end,
     * convert letters to numbers (A=10, B=11, ... Z=35), then the whole
     * numeric string mod 97 must equal 1.
     */
    private function hasValidChecksum(string $iban): bool
    {
        $rearranged = substr($iban, 4).substr($iban, 0, 4);

        $numeric = '';

        foreach (str_split($rearranged) as $char) {
            $numeric .= ctype_alpha($char)
                ? (string) (ord($char) - ord('A') + 10)
                : $char;
        }

        return $this->mod97($numeric) === 1;
    }

    /**
     * Compute the modulo of a large numeric string against 97 without
     * relying on the bcmath extension, by folding the number in chunks.
     */
    private function mod97(string $numeric): int
    {
        $remainder = 0;

        foreach (str_split($numeric) as $digit) {
            $remainder = ($remainder * 10 + (int) $digit) % 97;
        }

        return $remainder;
    }
}

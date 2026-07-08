<?php

namespace App\Support;

use App\Actions\Messaging\SendBroadcast;
use App\Livewire\Messaging\Broadcast;
use App\Rules\SaudiMobile;

/**
 * Normalization helper shared by the broadcast screen's manual number
 * input ({@see Broadcast}) and
 * {@see SendBroadcast}, so both agree on a single
 * canonical string per phone number when de-duplicating and validating.
 */
final class MobileNumber
{
    /**
     * Normalizes a Saudi mobile number to the local 05XXXXXXXX format
     * expected by {@see SaudiMobile}.
     *
     * Strips whitespace/separators and rewrites the common international
     * variants users type (+9665XXXXXXXX, 009665XXXXXXXX, 9665XXXXXXXX)
     * into 05XXXXXXXX. Anything else is returned unchanged (only
     * whitespace-stripped) so it still fails SaudiMobile validation with a
     * clear "invalid format" error instead of being silently mangled.
     */
    public static function normalize(string $raw): string
    {
        $value = trim($raw);
        $value = preg_replace('/[\s\-()]/', '', $value) ?? $value;

        if (preg_match('/^(?:\+966|00966|966)(5\d{8})$/', $value, $matches) === 1) {
            return '0'.$matches[1];
        }

        return $value;
    }
}

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

    /**
     * Converts a Saudi mobile number to the international `wa_id` format
     * WhatsApp/Meta expects: country code, no leading zero, no `+`
     * (e.g. 0560249160 → 966560249160). Numbers already in international
     * form are passed through with only `+`, a leading `00` and separators
     * stripped, so a WhatsApp send never goes out in the local `05…` form
     * (which Meta rejects) even though the message log keeps the local
     * number for display.
     */
    public static function toInternational(string $raw): string
    {
        $local = self::normalize($raw);

        if (preg_match('/^0(5\d{8})$/', $local, $matches) === 1) {
            return '966'.$matches[1];
        }

        $digits = preg_replace('/[\s\-()+]/', '', $raw) ?? $raw;

        return preg_replace('/^00/', '', $digits) ?? $digits;
    }
}

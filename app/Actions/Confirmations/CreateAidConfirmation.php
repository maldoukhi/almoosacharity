<?php

namespace App\Actions\Confirmations;

use App\Enums\AidStatus;
use App\Exceptions\Confirmations\AidConfirmationException;
use App\Models\Aid;
use App\Models\AidConfirmation;
use Illuminate\Support\Str;

/**
 * (Re)issues the single confirmation-link record for a delivered aid: a
 * fresh random token (never persisted in the clear — only its sha256
 * digest is), a new expiry, and — for a normal send — a clean tracking
 * slate. Keyed on aid_id via updateOrCreate, so a resend rotates this
 * same row rather than accumulating history: the previous link stops
 * working the instant a new one is issued.
 */
class CreateAidConfirmation
{
    /**
     * @param  bool  $preserveTracking  True for the automatic reminder
     *                                  flow: keeps the original sent_at/opened_at instead of resetting
     *                                  them, since a reminder is a continuation of the same outstanding
     *                                  request rather than a brand new one. Always false for a normal
     *                                  first send or an admin-triggered resend.
     * @return array{confirmation: AidConfirmation, rawToken: string}
     *
     * @throws AidConfirmationException
     */
    public function handle(Aid $aid, bool $preserveTracking = false): array
    {
        if ($aid->status !== AidStatus::Delivered) {
            throw AidConfirmationException::requiresDelivered();
        }

        // 24 alphanumeric chars ≈ 140 bits of entropy: still comfortably
        // unguessable, while keeping the token-only public link short.
        $rawToken = Str::random(24);

        $attributes = [
            'token_hash' => AidConfirmation::hashToken($rawToken),
            'expires_at' => now()->addDays((int) config('confirmations.ttl_days')),
            'confirmed_at' => null,
            'confirmed_ip' => null,
            'confirmed_user_agent' => null,
        ];

        if (! $preserveTracking) {
            $attributes['sent_at'] = null;
            $attributes['opened_at'] = null;
            $attributes['reminder_sent_at'] = null;
        }

        $confirmation = AidConfirmation::query()->updateOrCreate(
            ['aid_id' => $aid->id],
            $attributes,
        );

        return ['confirmation' => $confirmation, 'rawToken' => $rawToken];
    }
}

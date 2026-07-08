<?php

namespace App\Support;

use App\Actions\Confirmations\SendConfirmationLink;
use App\Models\AidConfirmation;

/**
 * Single source of truth for the beneficiary-facing "confirm receipt" URL.
 *
 * Isolating URL construction here decouples the code that *sends* the link
 * ({@see SendConfirmationLink}) from the routing
 * strategy itself, so the link format can be shortened without touching the
 * senders.
 *
 * The link is deliberately short: a token-only path (`/c/{token}`) rather
 * than a long signed URL. The raw token is the single secret — it is stored
 * only as a sha256 digest ({@see AidConfirmation::hashToken()}), is
 * cryptographically strong (~140 bits), and expiry is enforced by the model
 * itself, so no query-string signature is needed to keep the link secure.
 */
class ConfirmationLink
{
    /**
     * Build the confirmation URL for a raw (un-hashed) token.
     */
    public function url(AidConfirmation $confirmation, string $rawToken): string
    {
        return route('public.confirm', ['token' => $rawToken]);
    }
}

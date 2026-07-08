<?php

namespace App\Support;

use App\Actions\Confirmations\SendConfirmationLink;
use App\Models\AidConfirmation;
use Illuminate\Support\Facades\URL;

/**
 * Single source of truth for the beneficiary-facing "confirm receipt" URL.
 *
 * Isolating URL construction here decouples the code that *sends* the link
 * ({@see SendConfirmationLink}) from the routing
 * strategy itself, so the link format can be shortened without touching the
 * senders.
 */
class ConfirmationLink
{
    /**
     * Build the confirmation URL for a raw (un-hashed) token.
     */
    public function url(AidConfirmation $confirmation, string $rawToken): string
    {
        return URL::temporarySignedRoute(
            'public.confirm',
            $confirmation->expires_at,
            ['confirmation' => $confirmation->id, 'token' => $rawToken],
        );
    }
}

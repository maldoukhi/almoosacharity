<?php

namespace App\Support;

use App\Actions\Approvals\RequestBeneficiaryStageResponse;
use App\Models\BeneficiaryStageResponse;

/**
 * Single source of truth for the beneficiary-facing "respond to this
 * approval stage" URL. Mirrors {@see ConfirmationLink}.
 *
 * The link is deliberately short: a token-only path (`/r/{token}`) rather
 * than a long signed URL. The raw token is the single secret — it is
 * stored only as a sha256 digest ({@see BeneficiaryStageResponse::hashToken()}),
 * is cryptographically strong (~140 bits), and its expiry is enforced by
 * the model, so no query-string signature is needed to keep it secure.
 *
 * @see RequestBeneficiaryStageResponse for the sender.
 */
class BeneficiaryStageLink
{
    /**
     * Build the response URL for a raw (un-hashed) token.
     */
    public function url(BeneficiaryStageResponse $response, string $rawToken): string
    {
        return route('public.stage-response', ['token' => $rawToken]);
    }
}

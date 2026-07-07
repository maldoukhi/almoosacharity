<?php

namespace App\Exceptions\Confirmations;

use App\Exceptions\InvalidAidTransitionException;
use RuntimeException;

/**
 * Domain-specific transition errors for the delivery-confirmation flow —
 * mirrors {@see InvalidAidTransitionException}'s
 * named-constructor style, kept as its own small exception since this
 * domain (Confirmations) is self-contained.
 */
class AidConfirmationException extends RuntimeException
{
    /**
     * A confirmation link can only be (re)created while the aid is
     * currently Delivered.
     */
    public static function requiresDelivered(): self
    {
        return new self(__('confirmations.errors.requires_delivered'));
    }
}

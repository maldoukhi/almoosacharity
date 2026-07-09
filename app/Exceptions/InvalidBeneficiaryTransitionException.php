<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown whenever an action attempts an operation that a beneficiary's
 * current BeneficiaryStatus does not allow: submitting a beneficiary that
 * is not submittable, deciding on one that is not under review, or applying
 * a status change the BeneficiaryStatus transition graph forbids.
 */
class InvalidBeneficiaryTransitionException extends RuntimeException
{
    public static function notSubmittable(): self
    {
        return new self(__('beneficiaries.flow.errors.not_submittable'));
    }

    public static function notUnderReview(): self
    {
        return new self(__('beneficiaries.flow.errors.not_under_review'));
    }

    public static function noActiveFlow(): self
    {
        return new self(__('beneficiaries.flow.errors.no_active_flow'));
    }

    public static function invalidTransition(): self
    {
        return new self(__('beneficiaries.flow.errors.invalid_transition'));
    }
}

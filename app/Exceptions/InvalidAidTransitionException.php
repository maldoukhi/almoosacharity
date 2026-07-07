<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown whenever an action attempts an operation that an aid's current
 * AidStatus does not allow: editing/deleting a non-draft aid, cancelling
 * an aid that is no longer cancellable, submitting/deciding on an aid
 * that is not in the expected status, or applying a status change the
 * AidStatus transition graph forbids.
 */
class InvalidAidTransitionException extends RuntimeException
{
    public static function notEditable(): self
    {
        return new self(__('validation.custom.aid.not_editable'));
    }

    public static function notDeletable(): self
    {
        return new self(__('validation.custom.aid.not_deletable'));
    }

    public static function notCancellable(): self
    {
        return new self(__('validation.custom.aid.not_cancellable'));
    }

    public static function notUnderReview(): self
    {
        return new self(__('validation.custom.aid.not_under_review'));
    }

    public static function noActiveApprovalFlow(): self
    {
        return new self(__('validation.custom.aid.no_active_flow'));
    }
}

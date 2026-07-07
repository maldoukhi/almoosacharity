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

    /**
     * Disbursement can only be started on an Approved aid.
     */
    public static function notApprovedForDisbursement(): self
    {
        return new self(__('validation.custom.disbursement.not_approved'));
    }

    /**
     * A bank-transfer disbursement requires the beneficiary to already
     * have a registered IBAN on file.
     */
    public static function missingBankAccount(): self
    {
        return new self(__('validation.custom.disbursement.missing_bank_account'));
    }

    /**
     * Delivery can only be recorded while the aid/disbursement pair is
     * actually in the InDisbursement/Pending state.
     */
    public static function notInDisbursement(): self
    {
        return new self(__('validation.custom.disbursement.not_in_disbursement'));
    }

    /**
     * The administrative confirmation can only be recorded once the
     * disbursement has actually been delivered.
     */
    public static function notDeliveredForConfirmation(): self
    {
        return new self(__('validation.custom.disbursement.not_delivered'));
    }

    /**
     * A disbursement can only be confirmed once.
     */
    public static function alreadyConfirmed(): self
    {
        return new self(__('validation.custom.disbursement.already_confirmed'));
    }

    /**
     * A disbursement's reference/notes can only be corrected while it is
     * still pending delivery.
     */
    public static function notPendingForUpdate(): self
    {
        return new self(__('validation.custom.disbursement.not_pending_update'));
    }
}

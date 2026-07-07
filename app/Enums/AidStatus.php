<?php

namespace App\Enums;

enum AidStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case UnderReview = 'under_review';
    case Approved = 'approved';
    case InDisbursement = 'in_disbursement';
    case Delivered = 'delivered';
    case Confirmed = 'confirmed';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    /**
     * Human readable, translated label.
     */
    public function label(): string
    {
        return __('aids.status.'.$this->value);
    }

    /**
     * Semantic color token name for use with x-ui.badge (maps to the
     * --color-status-* design tokens defined in resources/css/app.css),
     * per the mapping documented in CLAUDE.md.
     */
    public function color(): string
    {
        return match ($this) {
            self::Draft, self::Cancelled => 'draft',
            self::Submitted, self::UnderReview => 'review',
            self::Approved, self::InDisbursement => 'approved',
            self::Delivered, self::Confirmed => 'delivered',
            self::Rejected => 'rejected',
        };
    }

    /**
     * The full status transition graph for an aid. Every action that
     * changes an aid's status (SubmitAid, RecordApprovalDecision,
     * CancelAid, ...) must only apply a transition allowed here.
     *
     * @return array<int, self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::Submitted],
            self::Submitted => [self::UnderReview, self::Cancelled],
            self::UnderReview => [self::Approved, self::Rejected, self::Draft, self::Cancelled],
            self::Approved => [self::InDisbursement],
            self::InDisbursement => [self::Delivered],
            self::Delivered => [self::Confirmed],
            self::Confirmed, self::Rejected, self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return in_array($to, $this->allowedTransitions(), true);
    }

    /**
     * Rejected/Cancelled are the failure/abort terminal states this method
     * has always tracked (kept as-is: nothing outside this enum currently
     * calls isFinal(), and Confirmed — the successful-completion terminal
     * state added in phase 6 — is deliberately not folded into it, since
     * it is a different kind of "done" than an aborted aid). Delivered
     * itself is still not final: it awaits the beneficiary delivery
     * confirmation flow to reach Confirmed.
     */
    public function isFinal(): bool
    {
        return in_array($this, [self::Rejected, self::Cancelled], true);
    }

    /**
     * Only a draft aid may have its core fields/items edited.
     */
    public function isEditable(): bool
    {
        return $this === self::Draft;
    }
}

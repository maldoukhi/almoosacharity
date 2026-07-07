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
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    /**
     * Human readable label. Hardcoded rather than routed through __():
     * this phase (3a) owns no resources/views/** or lang/**, mirroring the
     * precedent set by MessageChannel::label().
     */
    public function label(): string
    {
        return match ($this) {
            self::Draft => 'مسودة',
            self::Submitted => 'مقدَّمة',
            self::UnderReview => 'قيد المراجعة',
            self::Approved => 'معتمدة',
            self::InDisbursement => 'قيد الصرف',
            self::Delivered => 'مُسلَّمة',
            self::Rejected => 'مرفوضة',
            self::Cancelled => 'ملغاة',
        };
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
            self::Delivered => 'delivered',
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
            self::Delivered, self::Rejected, self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return in_array($to, $this->allowedTransitions(), true);
    }

    /**
     * Rejected/Cancelled are truly terminal states. Delivered is
     * intentionally not final: it awaits the beneficiary delivery
     * confirmation flow added in phase 6.
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

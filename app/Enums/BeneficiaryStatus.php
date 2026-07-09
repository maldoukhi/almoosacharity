<?php

namespace App\Enums;

enum BeneficiaryStatus: string
{
    /** Just registered, not yet submitted into the review workflow. */
    case New = 'new';

    /** Legacy pre-workflow state, kept valid and treated like New. */
    case UnderStudy = 'under_study';

    /** Moving through the configured beneficiary flow's stages. */
    case UnderReview = 'under_review';

    /** Approved and active (the successful terminal of the workflow). */
    case Active = 'active';

    /** Rejected during the workflow. */
    case Rejected = 'rejected';

    /** Temporarily suspended (off-sequence, reversible). */
    case Suspended = 'suspended';

    /** Deactivated (off-sequence): blocked from receiving new aids. */
    case Deactivated = 'deactivated';

    /**
     * Human readable, translated label.
     */
    public function label(): string
    {
        return __('beneficiaries.status.'.$this->value);
    }

    /**
     * Semantic color token name for use with x-ui.badge (maps to the
     * --color-status-* design tokens defined in resources/css/app.css).
     */
    public function color(): string
    {
        return match ($this) {
            self::Active => 'approved',
            self::UnderReview => 'review',
            self::Rejected, self::Suspended => 'rejected',
            self::New, self::UnderStudy, self::Deactivated => 'draft',
        };
    }

    /**
     * The full status transition graph for a beneficiary. Every action that
     * changes a beneficiary's status (SubmitBeneficiary,
     * RecordBeneficiaryDecision, DeactivateBeneficiary, ...) must only apply
     * a transition allowed here.
     *
     * @return array<int, self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::New, self::UnderStudy => [self::UnderReview, self::Deactivated],
            self::UnderReview => [self::Active, self::Rejected, self::New, self::Deactivated],
            self::Active => [self::Suspended, self::Deactivated, self::UnderReview],
            self::Suspended => [self::Active, self::Deactivated],
            self::Rejected => [self::New, self::Deactivated, self::UnderReview],
            self::Deactivated => [self::Active, self::New, self::UnderReview],
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return in_array($to, $this->allowedTransitions(), true);
    }

    /**
     * The states from which a beneficiary may be submitted into the review
     * workflow (New and its legacy equivalent UnderStudy).
     */
    public function isSubmittable(): bool
    {
        return in_array($this, [self::New, self::UnderStudy], true);
    }

    /**
     * Whether the beneficiary is currently moving through the workflow.
     */
    public function isUnderReview(): bool
    {
        return $this === self::UnderReview;
    }

    /**
     * Deactivated beneficiaries are archived off-sequence and must not be
     * granted new aids.
     */
    public function isDeactivated(): bool
    {
        return $this === self::Deactivated;
    }
}

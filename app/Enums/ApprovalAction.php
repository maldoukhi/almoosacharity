<?php

namespace App\Enums;

enum ApprovalAction: string
{
    case Approve = 'approve';
    case Reject = 'reject';
    case Return = 'return';

    /**
     * Human readable, translated label.
     */
    public function label(): string
    {
        return __('approvals.action.'.$this->value);
    }

    /**
     * Reject and Return decisions must be accompanied by an explanatory
     * note; Approve does not require one.
     */
    public function requiresNote(): bool
    {
        return match ($this) {
            self::Reject, self::Return => true,
            self::Approve => false,
        };
    }
}

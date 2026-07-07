<?php

namespace App\Enums;

enum ApprovalAction: string
{
    case Approve = 'approve';
    case Reject = 'reject';
    case Return = 'return';

    /**
     * Human readable label. Hardcoded rather than routed through __():
     * this phase (3a) owns no resources/views/** or lang/**, mirroring the
     * precedent set by MessageChannel::label().
     */
    public function label(): string
    {
        return match ($this) {
            self::Approve => 'اعتماد',
            self::Reject => 'رفض',
            self::Return => 'إرجاع للتعديل',
        };
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

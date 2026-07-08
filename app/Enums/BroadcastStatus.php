<?php

namespace App\Enums;

enum BroadcastStatus: string
{
    case Queued = 'queued';
    case Completed = 'completed';

    /**
     * Human readable label. See MessageChannel::label() for why this is
     * not routed through __() from this task.
     */
    public function label(): string
    {
        return match ($this) {
            self::Queued => 'قيد الإرسال',
            self::Completed => 'مكتملة',
        };
    }

    /**
     * Semantic color token name for use with x-ui.badge.
     */
    public function color(): string
    {
        return match ($this) {
            self::Queued => 'review',
            self::Completed => 'approved',
        };
    }
}

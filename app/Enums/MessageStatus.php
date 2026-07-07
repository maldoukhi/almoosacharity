<?php

namespace App\Enums;

enum MessageStatus: string
{
    case Pending = 'pending';
    case Sent = 'sent';
    case Failed = 'failed';

    /**
     * Human readable label. See MessageChannel::label() for why this is
     * not routed through __() in this task.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'قيد الإرسال',
            self::Sent => 'أُرسلت',
            self::Failed => 'فشلت',
        };
    }

    /**
     * Semantic color token name for use with x-ui.badge (maps to the
     * --color-status-* design tokens defined in resources/css/app.css).
     * There is no dedicated "pending" token yet, so Pending reuses the
     * amber "review" token (in-progress semantics) rather than adding a
     * new CSS token from this integrations-only task.
     */
    public function color(): string
    {
        return match ($this) {
            self::Pending => 'review',
            self::Sent => 'approved',
            self::Failed => 'rejected',
        };
    }
}

<?php

namespace App\Enums;

enum DisbursementStatus: string
{
    case Pending = 'pending';
    case Delivered = 'delivered';

    /**
     * Human readable, translated label.
     */
    public function label(): string
    {
        return __('disbursements.status.'.$this->value);
    }

    /**
     * Semantic color token name for use with x-ui.badge (maps to the
     * --color-status-* design tokens defined in resources/css/app.css).
     */
    public function color(): string
    {
        return match ($this) {
            self::Pending => 'review',
            self::Delivered => 'delivered',
        };
    }
}

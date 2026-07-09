<?php

namespace App\Enums;

/**
 * How much of an aid the beneficiary confirmed receiving, captured from the
 * public confirmation page. Drives the admin-facing "not fully received"
 * signals on the dashboard and the aids list.
 */
enum ReceiptStatus: string
{
    case Received = 'received';
    case Partial = 'partial';
    case NotReceived = 'not_received';

    public function label(): string
    {
        return __('confirmations.receipt_status.'.$this->value);
    }

    /**
     * A semantic status colour token (see resources/css/app.css) for badges.
     */
    public function color(): string
    {
        return match ($this) {
            self::Received => 'approved',
            self::Partial => 'review',
            self::NotReceived => 'rejected',
        };
    }

    /**
     * Whether this outcome needs staff attention (anything short of a full
     * receipt) — used by the dashboard/list "needs follow-up" surfaces.
     */
    public function needsAttention(): bool
    {
        return $this !== self::Received;
    }
}

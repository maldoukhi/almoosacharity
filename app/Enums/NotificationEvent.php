<?php

namespace App\Enums;

enum NotificationEvent: string
{
    // Beneficiary-facing aid lifecycle events (SMS/WhatsApp to the
    // beneficiary's mobile).
    case AidApproved = 'aid_approved';
    case AidReady = 'aid_ready';
    case AidDelivered = 'aid_delivered';

    // Staff-facing event: an aid has reached an approval stage a user must
    // act on. Notified to the assigned approvers (in-app bell + email +
    // WhatsApp, per the stage's chosen channels).
    case AidAwaitingApproval = 'aid_awaiting_approval';

    /**
     * Human readable, translated label.
     */
    public function label(): string
    {
        return __('notifications.events.'.$this->value);
    }

    /**
     * Whether this event targets staff/users (as opposed to beneficiaries).
     * Drives how the settings screen groups the editable templates.
     */
    public function isStaff(): bool
    {
        return $this === self::AidAwaitingApproval;
    }

    /**
     * The message channels for which this event has an editable template.
     * Beneficiary events keep SMS + WhatsApp (beneficiaries have no email
     * on file); the staff event uses Email + WhatsApp (its in-app bell is
     * handled by the database notification, not a per-channel template).
     *
     * @return array<int, MessageChannel>
     */
    public function channels(): array
    {
        return match ($this) {
            self::AidAwaitingApproval => [MessageChannel::Email, MessageChannel::WhatsApp],
            default => [MessageChannel::Sms, MessageChannel::WhatsApp],
        };
    }
}

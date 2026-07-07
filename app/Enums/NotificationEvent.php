<?php

namespace App\Enums;

enum NotificationEvent: string
{
    case AidApproved = 'aid_approved';
    case AidReady = 'aid_ready';
    case AidDelivered = 'aid_delivered';

    /**
     * Human readable, translated label.
     */
    public function label(): string
    {
        return __('notifications.events.'.$this->value);
    }
}

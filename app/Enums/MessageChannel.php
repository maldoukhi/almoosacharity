<?php

namespace App\Enums;

enum MessageChannel: string
{
    case Sms = 'sms';
    case WhatsApp = 'whatsapp';
    case Email = 'email';

    /**
     * Human readable, translated label (channel names live in the
     * notifications lang files so the settings/report/broadcast screens
     * stay fully localized).
     */
    public function label(): string
    {
        return __('notifications.channels.'.$this->value);
    }
}

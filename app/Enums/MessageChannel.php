<?php

namespace App\Enums;

enum MessageChannel: string
{
    case Sms = 'sms';
    case WhatsApp = 'whatsapp';

    /**
     * Human readable label. Not routed through __() translation files:
     * the settings/templates screen that will surface this label to
     * users ships in a later phase (this task owns no views/lang keys),
     * mirroring the precedent set by Locale::label().
     */
    public function label(): string
    {
        return match ($this) {
            self::Sms => 'رسالة نصية',
            self::WhatsApp => 'واتساب',
        };
    }
}

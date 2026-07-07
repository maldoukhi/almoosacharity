<?php

namespace App\Enums;

enum Locale: string
{
    case Ar = 'ar';
    case En = 'en';

    /**
     * Language names are displayed in their own script regardless of the
     * current app locale (e.g. a language switcher), so these are not
     * routed through __() translation files.
     */
    public function label(): string
    {
        return match ($this) {
            self::Ar => 'العربية',
            self::En => 'English',
        };
    }
}

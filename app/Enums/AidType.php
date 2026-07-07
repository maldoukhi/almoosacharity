<?php

namespace App\Enums;

enum AidType: string
{
    case Cash = 'cash';
    case InKind = 'in_kind';

    /**
     * Human readable, translated label.
     */
    public function label(): string
    {
        return __('aids.type.'.$this->value);
    }
}

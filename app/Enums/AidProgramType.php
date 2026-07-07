<?php

namespace App\Enums;

enum AidProgramType: string
{
    case Cash = 'cash';
    case InKind = 'in_kind';
    case Both = 'both';

    /**
     * Human readable, translated label.
     */
    public function label(): string
    {
        return __('aids.program_type.'.$this->value);
    }
}

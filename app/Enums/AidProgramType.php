<?php

namespace App\Enums;

enum AidProgramType: string
{
    case Cash = 'cash';
    case InKind = 'in_kind';
    case Both = 'both';

    /**
     * Human readable label. Hardcoded rather than routed through __():
     * this phase (3a) owns no resources/views/** or lang/**, mirroring the
     * precedent set by MessageChannel::label().
     */
    public function label(): string
    {
        return match ($this) {
            self::Cash => 'نقدي',
            self::InKind => 'عيني',
            self::Both => 'نقدي وعيني',
        };
    }
}

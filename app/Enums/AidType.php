<?php

namespace App\Enums;

enum AidType: string
{
    case Cash = 'cash';
    case InKind = 'in_kind';

    /**
     * Human readable label. Hardcoded rather than routed through __():
     * this phase (3a) owns no resources/views/** or lang/**, mirroring the
     * precedent set by MessageChannel::label(). A later phase's
     * translator/ui-builder agent should route this through lang keys once
     * a view surfaces it.
     */
    public function label(): string
    {
        return match ($this) {
            self::Cash => 'نقدية',
            self::InKind => 'عينية',
        };
    }
}

<?php

namespace App\Enums;

enum BeneficiaryStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
    case UnderStudy = 'under_study';

    /**
     * Human readable, translated label.
     */
    public function label(): string
    {
        return __('beneficiaries.status.'.$this->value);
    }

    /**
     * Semantic color token name for use with x-ui.badge (maps to the
     * --color-status-* design tokens defined in resources/css/app.css).
     */
    public function color(): string
    {
        return match ($this) {
            self::Active => 'approved',
            self::Suspended => 'rejected',
            self::UnderStudy => 'draft',
        };
    }
}

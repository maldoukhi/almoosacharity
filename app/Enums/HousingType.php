<?php

namespace App\Enums;

enum HousingType: string
{
    case Owned = 'owned';
    case Rented = 'rented';
    case Shared = 'shared';
    case Charity = 'charity';
    case Other = 'other';

    /**
     * Human readable, translated label.
     */
    public function label(): string
    {
        return __('beneficiaries.housing_type.'.$this->value);
    }
}

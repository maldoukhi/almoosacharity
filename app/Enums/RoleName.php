<?php

namespace App\Enums;

enum RoleName: string
{
    case SystemAdmin = 'system-admin';
    case SocialResearcher = 'social-researcher';
    case DataEntry = 'data-entry';
    case Manager = 'manager';

    /**
     * Human readable, translated label.
     */
    public function label(): string
    {
        return __('roles.names.'.$this->value);
    }
}

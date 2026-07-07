<?php

namespace App\Enums;

enum IdType: string
{
    case NationalId = 'national_id';
    case Iqama = 'iqama';

    /**
     * Human readable, translated label.
     */
    public function label(): string
    {
        return __('beneficiaries.id_type.'.$this->value);
    }
}

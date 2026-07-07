<?php

namespace App\Enums;

enum Gender: string
{
    case Male = 'male';
    case Female = 'female';

    /**
     * Human readable, translated label.
     */
    public function label(): string
    {
        return __('beneficiaries.gender.'.$this->value);
    }
}

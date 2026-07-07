<?php

namespace App\Enums;

enum MaritalStatus: string
{
    case Single = 'single';
    case Married = 'married';
    case Divorced = 'divorced';
    case Widowed = 'widowed';

    /**
     * Human readable, translated label.
     */
    public function label(): string
    {
        return __('beneficiaries.marital_status.'.$this->value);
    }
}

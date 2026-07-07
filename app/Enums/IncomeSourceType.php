<?php

namespace App\Enums;

enum IncomeSourceType: string
{
    case Salary = 'salary';
    case SocialSecurity = 'social_security';
    case Retirement = 'retirement';
    case CharitySupport = 'charity_support';
    case Other = 'other';

    /**
     * Human readable, translated label.
     */
    public function label(): string
    {
        return __('beneficiaries.income_source.'.$this->value);
    }
}

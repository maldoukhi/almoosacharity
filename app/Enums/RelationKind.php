<?php

namespace App\Enums;

enum RelationKind: string
{
    case Son = 'son';
    case Daughter = 'daughter';
    case Wife = 'wife';
    case Husband = 'husband';
    case Mother = 'mother';
    case Father = 'father';
    case Brother = 'brother';
    case Sister = 'sister';
    case Other = 'other';

    /**
     * Human readable, translated label.
     */
    public function label(): string
    {
        return __('beneficiaries.relation.'.$this->value);
    }
}

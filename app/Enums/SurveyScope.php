<?php

namespace App\Enums;

enum SurveyScope: string
{
    case General = 'general';
    case Program = 'program';

    /**
     * Human readable, translated label.
     */
    public function label(): string
    {
        return __('surveys.scope.'.$this->value);
    }
}

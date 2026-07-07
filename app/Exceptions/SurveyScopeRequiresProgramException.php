<?php

namespace App\Exceptions;

use App\Actions\Surveys\SaveSurvey;
use InvalidArgumentException;

/**
 * Thrown by {@see SaveSurvey} when a survey is saved
 * with scope=program but no aid_program_id: the friendly required_if
 * check already lives in the Livewire form, this is the action-level
 * backstop that keeps the rule true regardless of caller.
 */
class SurveyScopeRequiresProgramException extends InvalidArgumentException
{
    public static function make(): self
    {
        return new self(__('surveys.messages.scope_requires_program'));
    }
}

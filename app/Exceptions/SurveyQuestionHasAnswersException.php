<?php

namespace App\Exceptions;

use App\Actions\Surveys\SaveSurvey;
use RuntimeException;

/**
 * Thrown by {@see SaveSurvey} when the builder's
 * question list drops a question that already has recorded answers:
 * deleting it would silently orphan/lose response data, so it must stay
 * (only questions with zero answers may be removed).
 */
class SurveyQuestionHasAnswersException extends RuntimeException
{
    public static function forQuestion(string $label): self
    {
        return new self(__('surveys.messages.question_has_answers', ['label' => $label]));
    }
}

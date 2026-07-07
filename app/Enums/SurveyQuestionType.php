<?php

namespace App\Enums;

use App\Actions\Surveys\RecordSurveyResponse;
use Illuminate\Validation\Rule;

enum SurveyQuestionType: string
{
    case ShortText = 'short_text';
    case LongText = 'long_text';
    case SingleChoice = 'single_choice';
    case MultipleChoice = 'multiple_choice';
    case Rating = 'rating';
    case YesNo = 'yes_no';

    /**
     * Human readable, translated label.
     */
    public function label(): string
    {
        return __('surveys.question_type.'.$this->value);
    }

    /**
     * Whether this question type is backed by a configurable option list
     * (single/multiple choice), as opposed to free text/rating/yes-no.
     */
    public function hasOptions(): bool
    {
        return in_array($this, [self::SingleChoice, self::MultipleChoice], true);
    }

    /**
     * Single source of truth for validating a submitted answer's value,
     * used both by {@see RecordSurveyResponse} and
     * (for choice questions) the survey builder's own option-count check.
     *
     * $config may carry 'options' (array<int, array{value: string, label:
     * string}>, for choice questions) and 'max_stars' (int, for rating
     * questions, default 5). The returned rules validate a single field
     * named "value" (and "value.*" for multiple choice).
     *
     * @param  array{options?: array<int, array{value: string, label: string}>, max_stars?: int}  $config
     * @return array<string, array<int, mixed>>
     */
    public function validationRules(bool $required, array $config = []): array
    {
        $presence = $required ? 'required' : 'nullable';

        return match ($this) {
            self::ShortText => [
                'value' => [$presence, 'string', 'max:500'],
            ],
            self::LongText => [
                'value' => [$presence, 'string', 'max:5000'],
            ],
            self::SingleChoice => [
                'value' => [$presence, 'string', Rule::in($this->optionValues($config))],
            ],
            self::MultipleChoice => [
                'value' => [$presence, 'array'],
                'value.*' => ['string', Rule::in($this->optionValues($config))],
            ],
            self::Rating => [
                'value' => [$presence, 'integer', 'min:1', 'max:'.($config['max_stars'] ?? 5)],
            ],
            self::YesNo => [
                'value' => [$presence, 'boolean'],
            ],
        };
    }

    /**
     * @param  array{options?: array<int, array{value: string, label: string}>}  $config
     * @return array<int, string>
     */
    private function optionValues(array $config): array
    {
        return collect($config['options'] ?? [])->pluck('value')->map(fn ($value) => (string) $value)->all();
    }
}

<?php

namespace Database\Factories;

use App\Enums\SurveyQuestionType;
use App\Models\Survey;
use App\Models\SurveyQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SurveyQuestion>
 */
class SurveyQuestionFactory extends Factory
{
    protected $model = SurveyQuestion::class;

    public function definition(): array
    {
        return [
            'survey_id' => Survey::factory(),
            'type' => SurveyQuestionType::ShortText,
            'label' => $this->faker->sentence(6).'؟',
            'help_text' => null,
            'is_required' => true,
            'position' => 1,
            'options' => [],
            'config' => [],
        ];
    }

    public function singleChoice(): self
    {
        return $this->state(fn (): array => [
            'type' => SurveyQuestionType::SingleChoice,
            'options' => [
                ['value' => 'excellent', 'label' => 'ممتاز'],
                ['value' => 'good', 'label' => 'جيد'],
                ['value' => 'poor', 'label' => 'ضعيف'],
            ],
        ]);
    }

    public function rating(): self
    {
        return $this->state(fn (): array => [
            'type' => SurveyQuestionType::Rating,
            'config' => ['max_stars' => 5],
        ]);
    }

    public function yesNo(): self
    {
        return $this->state(fn (): array => ['type' => SurveyQuestionType::YesNo]);
    }
}

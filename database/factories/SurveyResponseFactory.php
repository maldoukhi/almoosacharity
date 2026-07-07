<?php

namespace Database\Factories;

use App\Models\Survey;
use App\Models\SurveyResponse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SurveyResponse>
 */
class SurveyResponseFactory extends Factory
{
    protected $model = SurveyResponse::class;

    public function definition(): array
    {
        return [
            'survey_id' => Survey::factory(),
            'aid_id' => null,
            'beneficiary_id' => null,
            'submitted_at' => now(),
            'ip' => $this->faker->ipv4(),
        ];
    }
}

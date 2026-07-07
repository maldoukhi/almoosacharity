<?php

namespace Database\Factories;

use App\Enums\SurveyScope;
use App\Models\AidProgram;
use App\Models\Survey;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Survey>
 */
class SurveyFactory extends Factory
{
    protected $model = Survey::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->boolean(60) ? $this->faker->sentence() : null,
            'is_active' => true,
            'scope' => SurveyScope::General,
            'aid_program_id' => null,
            'starts_at' => null,
            'ends_at' => null,
            'created_by' => User::query()->inRandomOrder()->first()?->id ?? User::factory(),
        ];
    }

    public function program(): self
    {
        return $this->state(fn (): array => [
            'scope' => SurveyScope::Program,
            'aid_program_id' => AidProgram::query()->inRandomOrder()->first()?->id ?? AidProgram::factory(),
        ]);
    }

    public function inactive(): self
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}

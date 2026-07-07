<?php

namespace Database\Factories;

use App\Enums\IncomeSourceType;
use App\Models\Beneficiary;
use App\Models\BeneficiaryIncomeSource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BeneficiaryIncomeSource>
 */
class BeneficiaryIncomeSourceFactory extends Factory
{
    protected $model = BeneficiaryIncomeSource::class;

    public function definition(): array
    {
        return [
            'beneficiary_id' => Beneficiary::factory(),
            'source_type' => $this->faker->randomElement(IncomeSourceType::cases()),
            'amount' => $this->faker->randomFloat(2, 100, 5000),
            'notes' => $this->faker->boolean(30) ? $this->faker->sentence() : null,
        ];
    }
}

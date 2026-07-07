<?php

namespace Database\Factories;

use App\Models\Aid;
use App\Models\AidConfirmation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AidConfirmation>
 */
class AidConfirmationFactory extends Factory
{
    protected $model = AidConfirmation::class;

    public function definition(): array
    {
        return [
            'aid_id' => Aid::factory(),
            'token_hash' => AidConfirmation::hashToken(Str::random(48)),
            'expires_at' => now()->addDays((int) config('confirmations.ttl_days')),
            'sent_at' => now()->subHour(),
            'channel' => 'sms',
        ];
    }

    /**
     * A confirmation the beneficiary has opened but not yet confirmed.
     */
    public function opened(): self
    {
        return $this->state(fn (array $attributes) => [
            'opened_at' => now()->subMinutes(30),
        ]);
    }

    /**
     * A confirmation the beneficiary has already completed.
     */
    public function confirmed(): self
    {
        return $this->state(fn (array $attributes) => [
            'opened_at' => now()->subMinutes(30),
            'confirmed_at' => now()->subMinutes(20),
            'confirmed_ip' => $this->faker->ipv4(),
            'confirmed_user_agent' => $this->faker->userAgent(),
        ]);
    }

    /**
     * A confirmation whose link has expired unconfirmed.
     */
    public function expired(): self
    {
        return $this->state(fn (array $attributes) => [
            'sent_at' => now()->subDays(10),
            'expires_at' => now()->subDays(3),
        ]);
    }
}

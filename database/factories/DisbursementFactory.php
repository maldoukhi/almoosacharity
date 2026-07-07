<?php

namespace Database\Factories;

use App\Enums\DisbursementMethod;
use App\Enums\DisbursementStatus;
use App\Models\Aid;
use App\Models\Disbursement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Disbursement>
 */
class DisbursementFactory extends Factory
{
    protected $model = Disbursement::class;

    public function definition(): array
    {
        $method = $this->faker->randomElement(DisbursementMethod::cases());
        $actor = User::query()->inRandomOrder()->first() ?? User::factory()->create();

        return [
            'aid_id' => Aid::factory(),
            'method' => $method,
            'status' => DisbursementStatus::Pending,
            'started_by' => $actor->id,
            'started_at' => now()->subDays($this->faker->numberBetween(1, 10)),
            'bank_account_masked' => $method->requiresBankAccount() ? 'SA•• •••• •••• ••'.$this->faker->numerify('####') : null,
            'bank_account_holder_snapshot' => $method->requiresBankAccount() ? $this->faker->name() : null,
        ];
    }

    /**
     * A disbursement whose delivery has already been recorded.
     */
    public function delivered(): self
    {
        return $this->state(function (array $attributes) {
            $method = $attributes['method'] ?? DisbursementMethod::OfficePickup;
            $isBankTransfer = $method === DisbursementMethod::BankTransfer;
            $isCourier = $method === DisbursementMethod::Courier;

            return [
                'status' => DisbursementStatus::Delivered,
                'delivered_by' => User::query()->inRandomOrder()->first()?->id ?? User::factory(),
                'delivered_at' => now()->subDays($this->faker->numberBetween(0, 5)),
                'transfer_reference' => $isBankTransfer ? $this->faker->bothify('TRX-########') : null,
                'receipt_number' => ! $isBankTransfer ? $this->faker->bothify('RCPT-######') : null,
                'courier_name' => $isCourier ? $this->faker->company() : null,
            ];
        });
    }

    /**
     * A delivered disbursement that has also been through the second
     * administrative confirmation.
     */
    public function confirmed(): self
    {
        return $this->delivered()->state(fn (array $attributes) => [
            'confirmed_by' => User::query()->inRandomOrder()->first()?->id ?? User::factory(),
            'confirmed_at' => now()->subDays($this->faker->numberBetween(0, 2)),
        ]);
    }
}

<?php

namespace Database\Factories;

use App\Enums\AidStatus;
use App\Enums\AidType;
use App\Models\Aid;
use App\Models\AidProgram;
use App\Models\ApprovalFlow;
use App\Models\Beneficiary;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Aid>
 */
class AidFactory extends Factory
{
    protected $model = Aid::class;

    private static int $referenceSequence = 0;

    private function generateUniqueReference(): string
    {
        self::$referenceSequence++;

        return 'AID-'.now()->format('Y').'-'.str_pad((string) self::$referenceSequence, 6, '0', STR_PAD_LEFT);
    }

    public function definition(): array
    {
        $program = AidProgram::query()->inRandomOrder()->first() ?? AidProgram::factory()->create();
        $beneficiary = Beneficiary::query()->inRandomOrder()->first() ?? Beneficiary::factory()->create();
        $createdBy = User::query()->whereHas('roles', fn ($q) => $q->whereIn('name', ['system-admin', 'data-entry']))->inRandomOrder()->first() ?? User::first();

        // Determine type based on program type
        $type = match ($program->type->value) {
            'cash' => AidType::Cash,
            'in_kind' => AidType::InKind,
            'both' => $this->faker->randomElement([AidType::Cash, AidType::InKind]),
        };

        $status = $this->faker->randomElement(AidStatus::cases());

        $flow = null;
        $currentStage = null;

        // If status requires approval flow information, set it
        if (in_array($status, [AidStatus::UnderReview, AidStatus::Submitted])) {
            $flow = ApprovalFlow::query()->where('is_active', true)->first();
            if ($flow) {
                $currentStage = $flow->stages()->first();
            }
        }

        return [
            'reference' => $this->generateUniqueReference(),
            'beneficiary_id' => $beneficiary->id,
            'aid_program_id' => $program->id,
            'type' => $type,
            'status' => $status,
            'amount' => $type === AidType::Cash ? $this->faker->randomFloat(2, 500, 5000) : null,
            'purpose' => $this->faker->sentence(4),
            'notes' => $this->faker->boolean(30) ? $this->faker->sentence() : null,
            'approval_flow_id' => $flow?->id,
            'current_stage_id' => $currentStage?->id,
            'created_by' => $createdBy->id,
            'submitted_at' => in_array($status, [AidStatus::UnderReview, AidStatus::Approved, AidStatus::Rejected, AidStatus::Cancelled, AidStatus::Delivered, AidStatus::InDisbursement, AidStatus::Submitted]) ? now()->subDays($this->faker->numberBetween(1, 30)) : null,
            'decided_at' => in_array($status, [AidStatus::Approved, AidStatus::Rejected, AidStatus::Delivered]) ? now()->subDays($this->faker->numberBetween(1, 20)) : null,
        ];
    }

    public function draft(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => AidStatus::Draft,
            'approval_flow_id' => null,
            'current_stage_id' => null,
            'submitted_at' => null,
            'decided_at' => null,
        ]);
    }

    public function underReview(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => AidStatus::UnderReview,
            'submitted_at' => now()->subDays($this->faker->numberBetween(1, 10)),
        ]);
    }

    public function approved(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => AidStatus::Approved,
            'approval_flow_id' => ApprovalFlow::query()->where('is_active', true)->first()?->id,
            'current_stage_id' => null,
            'submitted_at' => now()->subDays($this->faker->numberBetween(5, 20)),
            'decided_at' => now()->subDays($this->faker->numberBetween(1, 5)),
        ]);
    }

    public function rejected(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => AidStatus::Rejected,
            'current_stage_id' => null,
            'submitted_at' => now()->subDays($this->faker->numberBetween(5, 20)),
            'decided_at' => now()->subDays($this->faker->numberBetween(1, 5)),
        ]);
    }

    public function delivered(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => AidStatus::Delivered,
            'current_stage_id' => null,
            'submitted_at' => now()->subDays($this->faker->numberBetween(10, 40)),
            'decided_at' => now()->subDays($this->faker->numberBetween(5, 20)),
        ]);
    }
}

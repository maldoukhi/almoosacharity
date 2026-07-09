<?php

namespace App\Actions\Aids;

use App\Models\RecurringAidPlan;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Phase 10 recurring-aid generator: for every active plan due on or before
 * a given day, clone the plan's source aid into a fresh draft aid and
 * advance the plan to its next cycle. Idempotent per day — advancing
 * next_run_on past "today" the moment a cycle fires means a second run on
 * the same date generates nothing.
 *
 * Scheduled daily via routes/console.php (aids:generate-recurring).
 */
class GenerateRecurringAids
{
    public function __construct(
        private readonly CreateAid $createAid,
    ) {}

    /**
     * Process every due plan and return the number of aids generated.
     *
     * @param  CarbonImmutable|null  $today  Override "today" (tests); defaults to the current date.
     */
    public function handle(?CarbonImmutable $today = null): int
    {
        $today = ($today ?? CarbonImmutable::now())->startOfDay();

        $generated = 0;

        RecurringAidPlan::query()
            ->with('aid.items', 'aid.createdBy')
            ->where('is_active', true)
            ->whereDate('next_run_on', '<=', $today->toDateString())
            ->each(function (RecurringAidPlan $plan) use ($today, &$generated): void {
                if ($this->processPlan($plan, $today)) {
                    $generated++;
                }
            });

        return $generated;
    }

    /**
     * Generate one cycle for a single due plan. Returns true when a new aid
     * was actually cloned (false when the plan had already run past its
     * ends_on and was simply deactivated).
     */
    private function processPlan(RecurringAidPlan $plan, CarbonImmutable $today): bool
    {
        // Already past its end date: nothing more to generate, retire it.
        if ($plan->ends_on !== null && $plan->ends_on->startOfDay()->lt($today)) {
            $plan->update(['is_active' => false]);

            return false;
        }

        $source = $plan->aid;

        // Defensive: a soft-deleted / missing source can't be cloned.
        if ($source === null) {
            $plan->update(['is_active' => false]);

            return false;
        }

        $actor = $source->createdBy ?? User::query()->firstOrFail();

        DB::transaction(function () use ($plan, $source, $actor): void {
            $this->createAid->handle([
                'beneficiary_id' => $source->beneficiary_id,
                'aid_program_id' => $source->aid_program_id,
                'type' => $source->type->value,
                'amount' => $source->amount !== null ? (float) $source->amount : null,
                'purpose' => $source->purpose,
                'notes' => $source->notes,
                'items' => $source->items->map(fn ($item): array => [
                    'name' => $item->name,
                    'quantity' => $item->quantity,
                    'estimated_value' => $item->estimated_value !== null ? (float) $item->estimated_value : null,
                    'description' => $item->description,
                ])->all(),
            ], $actor);

            $next = $plan->frequency
                ->nextDate($plan->next_run_on->toImmutable(), $plan->interval_months)
                ->startOfDay();

            $plan->update([
                'next_run_on' => $next,
                // Deactivate once the newly computed run would fall past the
                // configured end date.
                'is_active' => ! ($plan->ends_on !== null && $next->gt($plan->ends_on->startOfDay())),
            ]);
        });

        return true;
    }
}

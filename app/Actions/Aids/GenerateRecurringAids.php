<?php

namespace App\Actions\Aids;

use App\Models\Aid;
use App\Models\RecurringAidPlan;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
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

        // Only active plans are ever considered, so a plan paused before its
        // lead window opened generates nothing. The lead-time comparison is
        // per-plan (lead_days lives on the row) so it is evaluated in PHP,
        // keeping the query SQLite/MySQL neutral.
        RecurringAidPlan::query()
            ->with('aid.items', 'aid.createdBy', 'aid.program', 'aid.beneficiary')
            ->where('is_active', true)
            ->each(function (RecurringAidPlan $plan) use ($today, &$generated): void {
                if ($this->isDue($plan, $today) && $this->processPlan($plan, $today)) {
                    $generated++;
                }
            });

        return $generated;
    }

    /**
     * A plan is due once "today" has reached its lead window: today is on or
     * after (next_run_on minus lead_days), and never before the schedule's
     * own starts_on. lead_days brings generation forward a configurable number
     * of days before the due date; a lead of 0 fires exactly on next_run_on.
     * A plan whose window is still in the future is skipped, so a series is
     * only ever built one cycle at a time — never the whole run upfront.
     */
    private function isDue(RecurringAidPlan $plan, CarbonImmutable $today): bool
    {
        // The schedule never fires before its own start date, even if the
        // lead window on the first due date would otherwise open earlier.
        if ($plan->starts_on !== null && $today->lt($plan->starts_on->startOfDay())) {
            return false;
        }

        $trigger = $plan->next_run_on
            ->toImmutable()
            ->startOfDay()
            ->subDays(max(0, $plan->lead_days));

        return $today->gte($trigger);
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
        $dueDate = $plan->next_run_on->toImmutable();

        DB::transaction(function () use ($plan, $dueDate, $actor): void {
            $this->cloneIntoSeries($plan, $actor, $dueDate);

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

    /**
     * Clone a plan's source aid into a fresh draft aid linked to the plan's
     * series, titled from the plan's title_template for the given cycle. Does
     * NOT advance the plan's schedule — used both by the scheduled processor
     * (which advances separately) and by the manual "add to series" action
     * (which adds an off-cycle aid without disturbing the schedule).
     *
     * The cycle number is derived from how many series aids already exist, so
     * the first generated aid is {n} = 1.
     */
    public function cloneIntoSeries(RecurringAidPlan $plan, User $actor, CarbonInterface $dueDate): Aid
    {
        $source = $plan->aid;

        $cycle = $plan->aids()->count() + 1;

        $clone = $this->createAid->handle([
            'beneficiary_id' => $source->beneficiary_id,
            'aid_program_id' => $source->aid_program_id,
            'type' => $source->type->value,
            'title' => $plan->renderTitle($cycle, $dueDate),
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

        // Link the clone into the plan's series (phase 10).
        $clone->update(['recurring_aid_plan_id' => $plan->id]);

        return $clone;
    }
}

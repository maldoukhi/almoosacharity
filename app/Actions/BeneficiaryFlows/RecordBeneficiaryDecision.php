<?php

namespace App\Actions\BeneficiaryFlows;

use App\Enums\ApprovalAction;
use App\Enums\BeneficiaryStatus;
use App\Exceptions\InvalidBeneficiaryTransitionException;
use App\Models\Beneficiary;
use App\Models\BeneficiaryDecision;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;

class RecordBeneficiaryDecision
{
    public function __construct(private readonly ResolveNextBeneficiaryStage $resolveNextStage) {}

    /**
     * Record a decision on a beneficiary's current review stage and move the
     * beneficiary forward accordingly:
     * - approve: advances to the next stage, or to Active (final stage
     *   cleared) if there is none.
     * - reject: moves the beneficiary to the final Rejected status.
     * - return: sends the beneficiary back to New for the creator to rework
     *   (the beneficiary_flow_id snapshot is kept for resubmission).
     *
     * @throws InvalidBeneficiaryTransitionException
     * @throws InvalidArgumentException
     * @throws AuthorizationException
     */
    public function handle(Beneficiary $beneficiary, User $actor, ApprovalAction $action, ?string $note = null): Beneficiary
    {
        if ($beneficiary->status !== BeneficiaryStatus::UnderReview || ! $beneficiary->current_stage_id) {
            throw InvalidBeneficiaryTransitionException::notUnderReview();
        }

        Gate::forUser($actor)->authorize('review', $beneficiary);

        $stage = $beneficiary->currentStage;

        if (! $stage->allows($action)) {
            throw new InvalidArgumentException(__('beneficiaries.flow.errors.action_not_allowed'));
        }

        if ($action->requiresNote() && blank($note)) {
            throw new InvalidArgumentException(__('beneficiaries.flow.errors.note_required'));
        }

        return DB::transaction(function () use ($beneficiary, $actor, $action, $note, $stage): Beneficiary {
            // Re-read under a row lock: two concurrent decisions on the same
            // beneficiary would otherwise both pass the pre-transaction status
            // check and record conflicting decisions.
            $locked = Beneficiary::query()->whereKey($beneficiary->id)->lockForUpdate()->first();

            if ($locked->status !== BeneficiaryStatus::UnderReview || $locked->current_stage_id !== $stage->id) {
                throw InvalidBeneficiaryTransitionException::notUnderReview();
            }

            BeneficiaryDecision::create([
                'beneficiary_id' => $beneficiary->id,
                'beneficiary_flow_stage_id' => $stage->id,
                'stage_name' => $stage->name,
                'user_id' => $actor->id,
                'action' => $action->value,
                'note' => $note,
                'decided_at' => now(),
            ]);

            match ($action) {
                ApprovalAction::Approve => $this->applyApprove($beneficiary, $actor),
                ApprovalAction::Reject => $this->applyReject($beneficiary, $actor),
                ApprovalAction::Return => $this->applyReturn($beneficiary, $actor),
            };

            return $beneficiary->fresh();
        });
    }

    private function applyApprove(Beneficiary $beneficiary, User $actor): void
    {
        $next = $this->resolveNextStage->handle($beneficiary);

        if ($next) {
            $beneficiary->update(['current_stage_id' => $next->id]);

            $this->log($beneficiary, $actor, 'stage-advanced', ['stage' => $next->name]);

            return;
        }

        $this->assertTransition($beneficiary, BeneficiaryStatus::Active);

        $beneficiary->update([
            'status' => BeneficiaryStatus::Active,
            'current_stage_id' => null,
            'decided_at' => now(),
        ]);

        $this->log($beneficiary, $actor, 'approved');
    }

    private function applyReject(Beneficiary $beneficiary, User $actor): void
    {
        $this->assertTransition($beneficiary, BeneficiaryStatus::Rejected);

        $beneficiary->update([
            'status' => BeneficiaryStatus::Rejected,
            'current_stage_id' => null,
            'decided_at' => now(),
        ]);

        $this->log($beneficiary, $actor, 'rejected');
    }

    private function applyReturn(Beneficiary $beneficiary, User $actor): void
    {
        $this->assertTransition($beneficiary, BeneficiaryStatus::New);

        $beneficiary->update([
            'status' => BeneficiaryStatus::New,
            'current_stage_id' => null,
        ]);

        $this->log($beneficiary, $actor, 'returned');
    }

    /**
     * @throws InvalidBeneficiaryTransitionException
     */
    private function assertTransition(Beneficiary $beneficiary, BeneficiaryStatus $to): void
    {
        if (! $beneficiary->status->canTransitionTo($to)) {
            throw InvalidBeneficiaryTransitionException::invalidTransition();
        }
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    private function log(Beneficiary $beneficiary, User $actor, string $event, array $properties = []): void
    {
        activity()
            ->performedOn($beneficiary)
            ->causedBy($actor)
            ->withProperties($properties)
            ->event($event)
            ->log('beneficiary.'.$event);
    }
}

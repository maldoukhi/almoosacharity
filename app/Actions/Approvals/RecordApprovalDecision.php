<?php

namespace App\Actions\Approvals;

use App\Enums\AidStatus;
use App\Enums\ApprovalAction;
use App\Events\Aids\AidApproved;
use App\Events\Approvals\AidEnteredStage;
use App\Exceptions\InvalidAidTransitionException;
use App\Models\Aid;
use App\Models\ApprovalDecision;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;

class RecordApprovalDecision
{
    public function __construct(private readonly ResolveNextStage $resolveNextStage) {}

    /**
     * Record a decision on an aid's current approval stage and move the
     * aid forward accordingly:
     * - approve: advances to the next stage, or to Approved (final stage
     *   cleared) if there is none.
     * - reject: moves the aid to the final Rejected status.
     * - return: sends the aid back to Draft for the creator to rework
     *   (the approval_flow_id snapshot is kept for when it is resubmitted).
     *
     * @throws InvalidAidTransitionException
     * @throws InvalidArgumentException
     * @throws AuthorizationException
     */
    public function handle(Aid $aid, User $actor, ApprovalAction $action, ?string $note = null): Aid
    {
        if ($aid->status !== AidStatus::UnderReview || ! $aid->current_stage_id) {
            throw InvalidAidTransitionException::notUnderReview();
        }

        Gate::forUser($actor)->authorize('act', $aid);

        $stage = $aid->currentStage;

        if (! $stage->allows($action)) {
            throw new InvalidArgumentException(__('validation.custom.approval.action_not_allowed'));
        }

        if ($action->requiresNote() && blank($note)) {
            throw new InvalidArgumentException(__('validation.custom.approval.note_required'));
        }

        return DB::transaction(function () use ($aid, $actor, $action, $note, $stage): Aid {
            // Re-read under a row lock: two concurrent decisions on the same
            // aid would otherwise both pass the pre-transaction status check
            // and record conflicting decisions.
            $locked = Aid::query()->whereKey($aid->id)->lockForUpdate()->first();

            if ($locked->status !== AidStatus::UnderReview || $locked->current_stage_id !== $stage->id) {
                throw InvalidAidTransitionException::notUnderReview();
            }

            ApprovalDecision::create([
                'aid_id' => $aid->id,
                'approval_flow_stage_id' => $stage->id,
                'stage_name' => $stage->name,
                'user_id' => $actor->id,
                'action' => $action->value,
                'note' => $note,
                'decided_at' => now(),
            ]);

            match ($action) {
                ApprovalAction::Approve => $this->applyApprove($aid),
                ApprovalAction::Reject => $this->applyReject($aid),
                ApprovalAction::Return => $this->applyReturn($aid),
            };

            return $aid->fresh();
        });
    }

    private function applyApprove(Aid $aid): void
    {
        $next = $this->resolveNextStage->handle($aid);

        if ($next) {
            $aid->update(['current_stage_id' => $next->id]);

            event(new AidEnteredStage($aid->refresh(), $next));

            return;
        }

        $this->assertTransition($aid, AidStatus::Approved);

        $aid->update([
            'status' => AidStatus::Approved,
            'current_stage_id' => null,
            'decided_at' => now(),
        ]);

        event(new AidApproved($aid->refresh()));
    }

    private function applyReject(Aid $aid): void
    {
        $this->assertTransition($aid, AidStatus::Rejected);

        $aid->update([
            'status' => AidStatus::Rejected,
            'current_stage_id' => null,
            'decided_at' => now(),
        ]);
    }

    private function applyReturn(Aid $aid): void
    {
        $this->assertTransition($aid, AidStatus::Draft);

        $aid->update([
            'status' => AidStatus::Draft,
            'current_stage_id' => null,
        ]);
    }

    /**
     * @throws InvalidAidTransitionException
     */
    private function assertTransition(Aid $aid, AidStatus $to): void
    {
        if (! $aid->status->canTransitionTo($to)) {
            throw InvalidAidTransitionException::notUnderReview();
        }
    }
}

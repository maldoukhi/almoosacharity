<?php

namespace App\Actions\Aids;

use App\Enums\AidStatus;
use App\Enums\AidType;
use App\Events\Approvals\AidEnteredStage;
use App\Exceptions\InvalidAidTransitionException;
use App\Models\Aid;
use App\Models\ApprovalFlow;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class SubmitAid
{
    /**
     * Submit a draft aid into the approval workflow.
     *
     * Validates, in order: the aid is still a draft, the actor is the
     * aid's creator, and the aid carries enough data to be reviewed
     * (an in-kind aid needs at least one item, a cash aid needs a
     * positive amount). It then resolves the approval flow to use — the
     * aid program's assigned flow if active, otherwise the default active
     * flow — and snapshots it onto the aid together with its first stage.
     *
     * @throws InvalidAidTransitionException
     * @throws AuthorizationException
     */
    public function handle(Aid $aid, User $actor): Aid
    {
        if ($aid->status !== AidStatus::Draft) {
            throw InvalidAidTransitionException::notEditable();
        }

        if ($aid->created_by !== $actor->id) {
            throw new AuthorizationException(__('validation.custom.aid.not_editable'));
        }

        $this->assertHasEnoughDataToSubmit($aid);

        $flow = $this->resolveFlowFor($aid);

        $firstStage = $flow->stages()->orderBy('order')->first();

        return DB::transaction(function () use ($aid, $flow, $firstStage): Aid {
            $aid->update([
                'approval_flow_id' => $flow->id,
                'current_stage_id' => $firstStage->id,
                'status' => AidStatus::UnderReview,
                'submitted_at' => now(),
            ]);

            event(new AidEnteredStage($aid->refresh(), $firstStage));

            return $aid->fresh();
        });
    }

    /**
     * @throws InvalidAidTransitionException
     */
    private function assertHasEnoughDataToSubmit(Aid $aid): void
    {
        if ($aid->type === AidType::InKind && $aid->items()->count() < 1) {
            throw new InvalidAidTransitionException(__('validation.custom.aid.no_items'));
        }

        if ($aid->type === AidType::Cash && (float) $aid->amount <= 0.0) {
            throw new InvalidAidTransitionException(__('validation.custom.aid.amount_required'));
        }
    }

    /**
     * @throws InvalidAidTransitionException
     */
    private function resolveFlowFor(Aid $aid): ApprovalFlow
    {
        $flow = $aid->program?->approvalFlow;

        if (! $flow || ! $flow->is_active) {
            $flow = ApprovalFlow::query()->default()->where('is_active', true)->first();
        }

        if (! $flow || $flow->stages()->count() === 0) {
            throw InvalidAidTransitionException::noActiveApprovalFlow();
        }

        return $flow;
    }
}

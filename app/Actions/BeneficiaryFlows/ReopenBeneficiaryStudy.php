<?php

namespace App\Actions\BeneficiaryFlows;

use App\Enums\BeneficiaryStatus;
use App\Exceptions\InvalidBeneficiaryTransitionException;
use App\Models\Beneficiary;
use App\Models\BeneficiaryFlow;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Re-open the study of a beneficiary whose review has already concluded
 * (Active / Rejected) or who was taken off-sequence (Deactivated), sending
 * them back into the review workflow at the first stage of their resolved
 * flow. This mirrors {@see SubmitBeneficiary}'s stage-resolution logic but
 * starts from a decided/off state rather than a fresh registration.
 */
class ReopenBeneficiaryStudy
{
    /**
     * @throws InvalidBeneficiaryTransitionException
     * @throws AuthorizationException
     */
    public function handle(Beneficiary $beneficiary, User $actor): Beneficiary
    {
        // Re-opening a decided case is a reviewer-level action: it reuses the
        // beneficiaries.review permission (the base ability behind the review
        // policy) rather than introducing a dedicated permission.
        Gate::forUser($actor)->authorize('beneficiaries.review');

        if (! $beneficiary->status->canTransitionTo(BeneficiaryStatus::UnderReview)) {
            throw InvalidBeneficiaryTransitionException::invalidTransition();
        }

        $flow = $this->resolveFlow($beneficiary);

        $firstStage = $flow->stages()->orderBy('order')->first();

        return DB::transaction(function () use ($beneficiary, $flow, $firstStage, $actor): Beneficiary {
            $beneficiary->update([
                'beneficiary_flow_id' => $flow->id,
                'current_stage_id' => $firstStage->id,
                'status' => BeneficiaryStatus::UnderReview,
                'decided_at' => null,
            ]);

            activity()
                ->performedOn($beneficiary)
                ->causedBy($actor)
                ->withProperties(['stage' => $firstStage->name])
                ->event('reopened')
                ->log('beneficiary.reopened');

            return $beneficiary->fresh();
        });
    }

    /**
     * Resolve the flow to re-study under: the beneficiary's own snapshotted
     * flow when it is still active and has stages, otherwise the default
     * active flow (same fallback as {@see SubmitBeneficiary}).
     *
     * @throws InvalidBeneficiaryTransitionException
     */
    private function resolveFlow(Beneficiary $beneficiary): BeneficiaryFlow
    {
        $flow = $beneficiary->beneficiaryFlow;

        if (! $flow || ! $flow->is_active || $flow->stages()->count() === 0) {
            $flow = BeneficiaryFlow::query()->default()->where('is_active', true)->first();
        }

        if (! $flow || $flow->stages()->count() === 0) {
            throw InvalidBeneficiaryTransitionException::noActiveFlow();
        }

        return $flow;
    }
}

<?php

namespace App\Actions\BeneficiaryFlows;

use App\Enums\BeneficiaryStatus;
use App\Exceptions\InvalidBeneficiaryTransitionException;
use App\Models\Beneficiary;
use App\Models\BeneficiaryFlow;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SubmitBeneficiary
{
    /**
     * Submit a newly-registered beneficiary into the review workflow.
     *
     * Resolves the flow to use (the default active beneficiary flow) and
     * snapshots it onto the beneficiary together with its first stage, moving
     * the beneficiary to UnderReview.
     *
     * @throws InvalidBeneficiaryTransitionException
     */
    public function handle(Beneficiary $beneficiary, User $actor): Beneficiary
    {
        if (! $beneficiary->status->isSubmittable()) {
            throw InvalidBeneficiaryTransitionException::notSubmittable();
        }

        $flow = $this->resolveFlow();

        $firstStage = $flow->stages()->orderBy('order')->first();

        return DB::transaction(function () use ($beneficiary, $flow, $firstStage, $actor): Beneficiary {
            $beneficiary->update([
                'beneficiary_flow_id' => $flow->id,
                'current_stage_id' => $firstStage->id,
                'status' => BeneficiaryStatus::UnderReview,
                'submitted_at' => now(),
                'decided_at' => null,
            ]);

            activity()
                ->performedOn($beneficiary)
                ->causedBy($actor)
                ->withProperties(['stage' => $firstStage->name])
                ->event('submitted')
                ->log('beneficiary.submitted');

            return $beneficiary->fresh();
        });
    }

    /**
     * @throws InvalidBeneficiaryTransitionException
     */
    private function resolveFlow(): BeneficiaryFlow
    {
        $flow = BeneficiaryFlow::query()->default()->where('is_active', true)->first();

        if (! $flow || $flow->stages()->count() === 0) {
            throw InvalidBeneficiaryTransitionException::noActiveFlow();
        }

        return $flow;
    }
}

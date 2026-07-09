<?php

namespace App\Actions\BeneficiaryFlows;

use App\Enums\BeneficiaryStatus;
use App\Exceptions\InvalidBeneficiaryTransitionException;
use App\Models\Beneficiary;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Off-sequence lifecycle transitions that live outside the review workflow:
 * deactivate (archive, blocks new aids), suspend (temporary), and reactivate
 * (back to Active). Each is guarded by the BeneficiaryStatus transition graph
 * and logged, so an invalid jump (e.g. deactivating an already-deactivated
 * beneficiary) is refused rather than silently applied.
 */
class DeactivateBeneficiary
{
    /**
     * Deactivate a beneficiary (archived, blocked from new aids).
     *
     * @throws InvalidBeneficiaryTransitionException
     */
    public function handle(Beneficiary $beneficiary, User $actor, ?string $note = null): Beneficiary
    {
        return $this->transition($beneficiary, $actor, BeneficiaryStatus::Deactivated, 'deactivated', $note);
    }

    /**
     * Suspend an active beneficiary (temporary, reversible).
     *
     * @throws InvalidBeneficiaryTransitionException
     */
    public function suspend(Beneficiary $beneficiary, User $actor, ?string $note = null): Beneficiary
    {
        return $this->transition($beneficiary, $actor, BeneficiaryStatus::Suspended, 'suspended', $note);
    }

    /**
     * Reactivate a suspended or deactivated beneficiary back to Active.
     *
     * @throws InvalidBeneficiaryTransitionException
     */
    public function reactivate(Beneficiary $beneficiary, User $actor, ?string $note = null): Beneficiary
    {
        return $this->transition($beneficiary, $actor, BeneficiaryStatus::Active, 'reactivated', $note);
    }

    /**
     * @throws InvalidBeneficiaryTransitionException
     */
    private function transition(Beneficiary $beneficiary, User $actor, BeneficiaryStatus $to, string $event, ?string $note): Beneficiary
    {
        if (! $beneficiary->status->canTransitionTo($to)) {
            throw InvalidBeneficiaryTransitionException::invalidTransition();
        }

        return DB::transaction(function () use ($beneficiary, $actor, $to, $event, $note): Beneficiary {
            $beneficiary->update([
                'status' => $to,
                // Leaving the workflow clears any in-flight stage pointer.
                'current_stage_id' => null,
            ]);

            activity()
                ->performedOn($beneficiary)
                ->causedBy($actor)
                ->withProperties(array_filter(['note' => $note]))
                ->event($event)
                ->log('beneficiary.'.$event);

            return $beneficiary->fresh();
        });
    }
}

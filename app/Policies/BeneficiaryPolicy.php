<?php

namespace App\Policies;

use App\Enums\BeneficiaryStatus;
use App\Models\Beneficiary;
use App\Models\User;

class BeneficiaryPolicy
{
    /**
     * System admins bypass this policy entirely via Gate::before, so every
     * method below only needs to check the relevant permission.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('beneficiaries.view');
    }

    public function view(User $user, Beneficiary $beneficiary): bool
    {
        return $user->can('beneficiaries.view');
    }

    public function create(User $user): bool
    {
        return $user->can('beneficiaries.create');
    }

    public function update(User $user, Beneficiary $beneficiary): bool
    {
        return $user->can('beneficiaries.update');
    }

    public function delete(User $user, Beneficiary $beneficiary): bool
    {
        return $user->can('beneficiaries.delete');
    }

    public function restore(User $user, Beneficiary $beneficiary): bool
    {
        return $user->can('beneficiaries.restore');
    }

    /**
     * Reveal the decrypted IBAN / bank account holder name.
     */
    public function viewBankData(User $user, Beneficiary $beneficiary): bool
    {
        return $user->can('beneficiaries.bank-data.view');
    }

    /**
     * Create/update the IBAN / bank account holder name.
     */
    public function manageBankData(User $user, Beneficiary $beneficiary): bool
    {
        return $user->can('beneficiaries.bank-data.manage');
    }

    /**
     * Submit a newly-registered beneficiary into the review workflow.
     * Only allowed from a submittable state (New / legacy UnderStudy).
     */
    public function submit(User $user, Beneficiary $beneficiary): bool
    {
        return $user->can('beneficiaries.submit')
            && $beneficiary->status->isSubmittable();
    }

    /**
     * Take a review action (approve/reject/return) on the beneficiary's
     * current stage: requires beneficiaries.review and that the actor is
     * eligible for that stage — i.e. holds its role or is one of its
     * specifically-assigned users (see {@see BeneficiaryFlowStage::allowsUser()}).
     */
    public function review(User $user, Beneficiary $beneficiary): bool
    {
        return $user->can('beneficiaries.review')
            && $beneficiary->currentStage !== null
            && $beneficiary->currentStage->allowsUser($user);
    }

    /**
     * Deactivate (archive) a beneficiary, blocking new aids.
     */
    public function deactivate(User $user, Beneficiary $beneficiary): bool
    {
        return $user->can('beneficiaries.deactivate')
            && $beneficiary->status->canTransitionTo(BeneficiaryStatus::Deactivated);
    }

    /**
     * Suspend an active beneficiary (temporary, reversible).
     */
    public function suspend(User $user, Beneficiary $beneficiary): bool
    {
        return $user->can('beneficiaries.deactivate')
            && $beneficiary->status->canTransitionTo(BeneficiaryStatus::Suspended);
    }

    /**
     * Reactivate a suspended/deactivated beneficiary back to Active.
     */
    public function reactivate(User $user, Beneficiary $beneficiary): bool
    {
        return $user->can('beneficiaries.deactivate')
            && $beneficiary->status->canTransitionTo(BeneficiaryStatus::Active);
    }

    public function import(User $user): bool
    {
        return $user->can('beneficiaries.import');
    }

    public function export(User $user): bool
    {
        return $user->can('beneficiaries.export');
    }
}

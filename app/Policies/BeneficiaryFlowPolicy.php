<?php

namespace App\Policies;

use App\Models\BeneficiaryFlow;
use App\Models\User;

class BeneficiaryFlowPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('beneficiaries.flows.configure');
    }

    /**
     * Single ability covering both creating and updating a beneficiary flow
     * (and its stages).
     */
    public function manage(User $user, ?BeneficiaryFlow $flow = null): bool
    {
        return $user->can('beneficiaries.flows.configure');
    }
}

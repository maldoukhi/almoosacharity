<?php

namespace App\Policies;

use App\Enums\DisbursementStatus;
use App\Models\Aid;
use App\Models\Disbursement;
use App\Models\User;

class DisbursementPolicy
{
    /**
     * System admins bypass this policy entirely via Gate::before, so every
     * method below only needs to check the relevant permission.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('disbursements.view');
    }

    public function view(User $user, Disbursement $disbursement): bool
    {
        return $user->can('disbursements.view');
    }

    /**
     * Start disbursing an Approved aid. Resolved via the
     * `Gate::authorize('start', [Disbursement::class, $aid])` array
     * convention since no Disbursement row exists yet when this is
     * checked; the aid's actual status is re-verified for real inside
     * StartDisbursement under a row lock.
     */
    public function start(User $user, Aid $aid): bool
    {
        return $user->can('disbursements.manage');
    }

    public function record(User $user, Disbursement $disbursement): bool
    {
        return $user->can('disbursements.manage');
    }

    public function confirm(User $user, Disbursement $disbursement): bool
    {
        return $user->can('disbursements.confirm');
    }

    public function update(User $user, Disbursement $disbursement): bool
    {
        return $user->can('disbursements.manage') && $disbursement->status === DisbursementStatus::Pending;
    }
}

<?php

namespace App\Policies;

use App\Models\AidConfirmation;
use App\Models\User;

/**
 * System admins bypass this policy entirely via Gate::before, so every
 * method below only needs to check the relevant permission — both reuse
 * the existing disbursements permissions (the confirmation link is part
 * of the same disbursement/delivery domain) rather than introducing new
 * ones.
 */
class AidConfirmationPolicy
{
    public function view(User $user, AidConfirmation $confirmation): bool
    {
        return $user->can('disbursements.view');
    }

    public function resend(User $user, AidConfirmation $confirmation): bool
    {
        return $user->can('disbursements.confirm');
    }
}

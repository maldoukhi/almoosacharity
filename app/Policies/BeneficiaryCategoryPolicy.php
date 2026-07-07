<?php

namespace App\Policies;

use App\Models\BeneficiaryCategory;
use App\Models\User;

class BeneficiaryCategoryPolicy
{
    /**
     * System admins bypass this policy entirely via Gate::before, so every
     * method below only needs to check the relevant permission.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('settings.view');
    }

    public function create(User $user): bool
    {
        return $user->can('settings.manage');
    }

    public function update(User $user, BeneficiaryCategory $beneficiaryCategory): bool
    {
        return $user->can('settings.manage');
    }

    public function delete(User $user, BeneficiaryCategory $beneficiaryCategory): bool
    {
        return $user->can('settings.manage');
    }
}

<?php

namespace App\Policies;

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

    public function import(User $user): bool
    {
        return $user->can('beneficiaries.import');
    }

    public function export(User $user): bool
    {
        return $user->can('beneficiaries.export');
    }
}

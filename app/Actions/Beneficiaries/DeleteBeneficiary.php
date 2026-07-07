<?php

namespace App\Actions\Beneficiaries;

use App\Models\Beneficiary;

class DeleteBeneficiary
{
    /**
     * Soft delete the given beneficiary.
     */
    public function handle(Beneficiary $beneficiary): void
    {
        $beneficiary->delete();
    }
}

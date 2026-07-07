<?php

namespace App\Actions\Beneficiaries;

use App\Models\Beneficiary;

class RestoreBeneficiary
{
    /**
     * Restore a previously soft-deleted beneficiary.
     */
    public function handle(Beneficiary $beneficiary): void
    {
        $beneficiary->restore();
    }
}

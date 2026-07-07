<?php

namespace App\Actions\Beneficiaries;

use App\Models\Beneficiary;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class RevealBankData
{
    /**
     * Authorize and reveal the decrypted IBAN / bank account holder name
     * for the given beneficiary, recording an activity log entry for the
     * reveal itself (never the values).
     *
     * @return array{iban: ?string, holder: ?string}
     */
    public function handle(Beneficiary $beneficiary, User $actor): array
    {
        Gate::forUser($actor)->authorize('viewBankData', $beneficiary);

        activity('bank-data-reveal')
            ->causedBy($actor)
            ->performedOn($beneficiary)
            ->log('bank data revealed');

        return [
            'iban' => $beneficiary->iban,
            'holder' => $beneficiary->bank_account_holder,
        ];
    }
}

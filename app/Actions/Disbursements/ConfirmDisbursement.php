<?php

namespace App\Actions\Disbursements;

use App\Enums\DisbursementStatus;
use App\Exceptions\InvalidAidTransitionException;
use App\Models\Disbursement;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * A second, purely administrative sign-off on a delivered disbursement —
 * distinct from the beneficiary's own delivery-confirmation link added in
 * phase 6. Requires the disbursements.confirm permission and cannot be
 * repeated once recorded.
 */
class ConfirmDisbursement
{
    /**
     * @throws InvalidAidTransitionException
     * @throws AuthorizationException
     */
    public function handle(Disbursement $disbursement, User $actor): Disbursement
    {
        Gate::forUser($actor)->authorize('confirm', $disbursement);

        return DB::transaction(function () use ($disbursement, $actor): Disbursement {
            $locked = Disbursement::query()->whereKey($disbursement->id)->lockForUpdate()->first();

            if ($locked->status !== DisbursementStatus::Delivered) {
                throw InvalidAidTransitionException::notDeliveredForConfirmation();
            }

            if ($locked->confirmed_at !== null) {
                throw InvalidAidTransitionException::alreadyConfirmed();
            }

            $locked->update([
                'confirmed_by' => $actor->id,
                'confirmed_at' => now(),
            ]);

            return $locked->fresh();
        });
    }
}

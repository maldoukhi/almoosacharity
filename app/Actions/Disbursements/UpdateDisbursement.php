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
 * Corrects the reference/notes captured on a disbursement while it is
 * still pending — i.e. before delivery has actually been recorded. The
 * method and any post-delivery fields are never editable through this
 * action.
 */
class UpdateDisbursement
{
    /**
     * @param  array{transfer_reference?: ?string, receipt_number?: ?string, courier_name?: ?string, notes?: ?string}  $data
     *
     * @throws InvalidAidTransitionException
     * @throws AuthorizationException
     */
    public function handle(Disbursement $disbursement, User $actor, array $data): Disbursement
    {
        Gate::forUser($actor)->authorize('update', $disbursement);

        return DB::transaction(function () use ($disbursement, $data): Disbursement {
            $locked = Disbursement::query()->whereKey($disbursement->id)->lockForUpdate()->first();

            if ($locked->status !== DisbursementStatus::Pending) {
                throw InvalidAidTransitionException::notPendingForUpdate();
            }

            $locked->update(array_intersect_key($data, array_flip([
                'transfer_reference', 'receipt_number', 'courier_name', 'notes',
            ])));

            return $locked->fresh();
        });
    }
}

<?php

namespace App\Actions\Disbursements;

use App\Enums\AidStatus;
use App\Enums\DisbursementMethod;
use App\Enums\DisbursementStatus;
use App\Events\Aids\AidReadyForCollection;
use App\Exceptions\InvalidAidTransitionException;
use App\Models\Aid;
use App\Models\Disbursement;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Moves an Approved aid into disbursement: selects the delivery method,
 * snapshots the beneficiary's bank details for a bank transfer, and opens
 * the disbursement record that RecordDelivery will later complete.
 */
class StartDisbursement
{
    /**
     * @throws InvalidAidTransitionException
     * @throws AuthorizationException
     */
    public function handle(Aid $aid, DisbursementMethod $method, User $actor): Disbursement
    {
        // Resolved via the `[Disbursement::class, $aid]` array convention:
        // no Disbursement row exists yet at this point, so the ability is
        // checked against DisbursementPolicy::start(User, Aid) instead of
        // an AidPolicy method.
        Gate::forUser($actor)->authorize('start', [Disbursement::class, $aid]);

        if ($method->requiresBankAccount() && ! $aid->beneficiary->iban) {
            throw InvalidAidTransitionException::missingBankAccount();
        }

        return DB::transaction(function () use ($aid, $method, $actor): Disbursement {
            // Re-read under a row lock: two concurrent "start disbursement"
            // clicks would otherwise both pass the pre-transaction status
            // check and create duplicate disbursement records for the same
            // aid (the aid_id unique constraint is the last line of
            // defense, but the status check must run under the lock too).
            $locked = Aid::query()->whereKey($aid->id)->lockForUpdate()->first();

            if ($locked->status !== AidStatus::Approved || ! $locked->status->canTransitionTo(AidStatus::InDisbursement)) {
                throw InvalidAidTransitionException::notApprovedForDisbursement();
            }

            $beneficiary = $locked->beneficiary;

            $disbursement = Disbursement::create([
                'aid_id' => $locked->id,
                'method' => $method,
                'status' => DisbursementStatus::Pending,
                'started_by' => $actor->id,
                'started_at' => now(),
                'bank_account_masked' => $method->requiresBankAccount() ? $beneficiary->maskedIban() : null,
                'bank_account_holder_snapshot' => $method->requiresBankAccount() ? $beneficiary->bank_account_holder : null,
            ]);

            $locked->update(['status' => AidStatus::InDisbursement]);

            event(new AidReadyForCollection($locked->refresh()));

            return $disbursement;
        });
    }
}

<?php

namespace App\Actions\Disbursements;

use App\Enums\AidStatus;
use App\Enums\DisbursementMethod;
use App\Enums\DisbursementStatus;
use App\Events\Aids\AidDelivered;
use App\Exceptions\InvalidAidTransitionException;
use App\Models\Aid;
use App\Models\Disbursement;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Records the actual hand-off of an in-disbursement aid to its
 * beneficiary: who delivered it, when, the method-appropriate reference,
 * and an optional proof-of-delivery file. Does not dispatch any events
 * itself — the notification wiring for the beneficiary confirmation link
 * (phase 6) is added on top of this action separately.
 */
class RecordDelivery
{
    /**
     * @param  array{delivered_at?: \DateTimeInterface|string|null, transfer_reference?: ?string, receipt_number?: ?string, courier_name?: ?string, notes?: ?string, proof?: ?UploadedFile}  $data
     *
     * @throws InvalidAidTransitionException
     * @throws AuthorizationException
     */
    public function handle(Disbursement $disbursement, User $actor, array $data): Disbursement
    {
        Gate::forUser($actor)->authorize('record', $disbursement);

        return DB::transaction(function () use ($disbursement, $actor, $data): Disbursement {
            // Lock the parent aid (same convention as
            // RecordApprovalDecision/StartDisbursement) so a concurrent
            // second delivery attempt can't race past the status check
            // below, then re-read the disbursement row itself.
            $lockedAid = Aid::query()->whereKey($disbursement->aid_id)->lockForUpdate()->first();
            $lockedDisbursement = Disbursement::query()->whereKey($disbursement->id)->first();

            if ($lockedAid->status !== AidStatus::InDisbursement || $lockedDisbursement->status !== DisbursementStatus::Pending) {
                throw InvalidAidTransitionException::notInDisbursement();
            }

            $method = $lockedDisbursement->method;
            $isBankTransfer = $method === DisbursementMethod::BankTransfer;
            $isCourier = $method === DisbursementMethod::Courier;

            $lockedDisbursement->update([
                'delivered_by' => $actor->id,
                'delivered_at' => $data['delivered_at'] ?? now(),
                'transfer_reference' => $isBankTransfer ? ($data['transfer_reference'] ?? null) : null,
                'receipt_number' => ! $isBankTransfer ? ($data['receipt_number'] ?? null) : null,
                'courier_name' => $isCourier ? ($data['courier_name'] ?? null) : null,
                'notes' => $data['notes'] ?? null,
                'status' => DisbursementStatus::Delivered,
            ]);

            if (($data['proof'] ?? null) instanceof UploadedFile) {
                $lockedDisbursement
                    ->addMedia($data['proof']->getRealPath())
                    ->usingName($data['proof']->getClientOriginalName())
                    ->toMediaCollection('delivery_proof');
            }

            $lockedAid->update(['status' => AidStatus::Delivered]);

            event(new AidDelivered($lockedAid->refresh()));

            return $lockedDisbursement->fresh();
        });
    }
}

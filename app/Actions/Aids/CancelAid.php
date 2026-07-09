<?php

namespace App\Actions\Aids;

use App\Enums\AidStatus;
use App\Exceptions\InvalidAidTransitionException;
use App\Models\Aid;
use App\Support\FiscalLock;

class CancelAid
{
    /**
     * Cancel an aid that has not yet been decided on. Who is allowed to
     * call this (the creator, or a user with aids.delete) is enforced by
     * AidPolicy::cancel(); this action only enforces the status guard.
     *
     * @throws InvalidAidTransitionException
     */
    public function handle(Aid $aid): Aid
    {
        FiscalLock::assertMutable($aid);

        $cancellableStatuses = [AidStatus::Draft, AidStatus::Submitted, AidStatus::UnderReview];

        if (! in_array($aid->status, $cancellableStatuses, true)) {
            throw InvalidAidTransitionException::notCancellable();
        }

        $aid->update([
            'status' => AidStatus::Cancelled,
            'current_stage_id' => null,
            'decided_at' => now(),
        ]);

        return $aid->fresh();
    }
}

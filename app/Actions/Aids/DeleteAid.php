<?php

namespace App\Actions\Aids;

use App\Enums\AidStatus;
use App\Exceptions\InvalidAidTransitionException;
use App\Models\Aid;

class DeleteAid
{
    /**
     * Soft delete a draft aid. Any other status is rejected: once an aid
     * has been submitted it is part of the workflow's audit trail and
     * must instead be cancelled (see CancelAid).
     *
     * @throws InvalidAidTransitionException
     */
    public function handle(Aid $aid): void
    {
        if ($aid->status !== AidStatus::Draft) {
            throw InvalidAidTransitionException::notDeletable();
        }

        $aid->delete();
    }
}

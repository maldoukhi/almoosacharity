<?php

namespace App\Policies;

use App\Enums\AidStatus;
use App\Models\Aid;
use App\Models\User;

class AidPolicy
{
    /**
     * System admins bypass this policy entirely via Gate::before, so every
     * method below only needs to check the relevant permission.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('aids.view');
    }

    /**
     * aids.view grants visibility into one's own aids; aids.view-any is
     * required to see aids created by other users.
     */
    public function view(User $user, Aid $aid): bool
    {
        if (! $user->can('aids.view')) {
            return false;
        }

        return $user->can('aids.view-any') || $user->id === $aid->created_by;
    }

    public function create(User $user): bool
    {
        return $user->can('aids.create');
    }

    public function update(User $user, Aid $aid): bool
    {
        return $user->can('aids.update') && $aid->status->isEditable();
    }

    public function delete(User $user, Aid $aid): bool
    {
        return $user->can('aids.delete') && $aid->status === AidStatus::Draft;
    }

    public function submit(User $user, Aid $aid): bool
    {
        return $user->can('aids.submit')
            && $user->id === $aid->created_by
            && $aid->status === AidStatus::Draft;
    }

    public function cancel(User $user, Aid $aid): bool
    {
        $isCreatorOrManager = $user->id === $aid->created_by || $user->can('aids.delete');

        $cancellableStatuses = [AidStatus::Draft, AidStatus::Submitted, AidStatus::UnderReview];

        return $isCreatorOrManager && in_array($aid->status, $cancellableStatuses, true);
    }

    /**
     * Take an approval action (approve/reject/return) on the aid's
     * current stage: requires approvals.act and that the actor holds the
     * role assigned to that stage.
     */
    public function act(User $user, Aid $aid): bool
    {
        return $user->can('approvals.act')
            && $aid->currentStage !== null
            && $user->hasRole($aid->currentStage->role);
    }
}

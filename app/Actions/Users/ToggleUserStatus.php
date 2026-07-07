<?php

namespace App\Actions\Users;

use App\Enums\UserStatus;
use App\Models\User;

class ToggleUserStatus
{
    /**
     * Flip the given user between active and suspended.
     */
    public function handle(User $user): User
    {
        $user->update([
            'status' => $user->status === UserStatus::Active
                ? UserStatus::Suspended
                : UserStatus::Active,
        ]);

        return $user->fresh();
    }
}

<?php

namespace App\Actions\Users;

use App\Models\User;

class DeleteUser
{
    /**
     * Soft delete the given user.
     */
    public function handle(User $user): void
    {
        $user->delete();
    }
}

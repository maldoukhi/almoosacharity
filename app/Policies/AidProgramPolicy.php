<?php

namespace App\Policies;

use App\Models\AidProgram;
use App\Models\User;

class AidProgramPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('settings.view');
    }

    /**
     * Single ability covering both creating and updating an aid program.
     */
    public function manage(User $user, ?AidProgram $program = null): bool
    {
        return $user->can('settings.manage');
    }
}

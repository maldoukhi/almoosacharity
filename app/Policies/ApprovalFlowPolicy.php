<?php

namespace App\Policies;

use App\Models\ApprovalFlow;
use App\Models\User;

class ApprovalFlowPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('approvals.configure');
    }

    /**
     * Single ability covering both creating and updating an approval flow
     * (and its stages).
     */
    public function manage(User $user, ?ApprovalFlow $flow = null): bool
    {
        return $user->can('approvals.configure');
    }
}

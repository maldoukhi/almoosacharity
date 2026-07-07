<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * System admins bypass this policy entirely via Gate::before, so every
     * method below only needs to check the relevant permission.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('users.view');
    }

    public function view(User $user, User $model): bool
    {
        return $user->can('users.view');
    }

    public function create(User $user): bool
    {
        return $user->can('users.create');
    }

    public function update(User $user, User $model): bool
    {
        return $user->can('users.update');
    }

    /**
     * Users may never delete their own account, even with the permission.
     */
    public function delete(User $user, User $model): bool
    {
        if ($user->is($model)) {
            return false;
        }

        return $user->can('users.delete');
    }

    /**
     * Users may never suspend their own account, even with the permission.
     */
    public function suspend(User $user, User $model): bool
    {
        if ($user->is($model)) {
            return false;
        }

        return $user->can('users.suspend');
    }
}

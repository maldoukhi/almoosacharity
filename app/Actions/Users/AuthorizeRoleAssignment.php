<?php

namespace App\Actions\Users;

use App\Enums\RoleName;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;

class AuthorizeRoleAssignment
{
    /**
     * Guard against privilege escalation via role assignment.
     *
     * Two independent rules apply, both keyed off the currently
     * authenticated actor (never the model being created/updated):
     *
     * 1. Assigning or changing a user's role requires the `roles.assign`
     *    permission. Holding `users.create`/`users.update` alone is not
     *    enough — those only grant basic account management.
     * 2. The `system-admin` role may only ever be assigned by an actor who
     *    is themselves a system-admin, regardless of `roles.assign`.
     *
     * This is called from both the Livewire Form (for a friendly inline
     * error) and from CreateUser/UpdateUser (the source of truth, since the
     * Livewire-level check is trivially bypassable by calling the action
     * directly or manipulating the component over the wire).
     *
     * @throws AuthorizationException
     */
    public function handle(string $role, ?string $currentRole): void
    {
        $actor = Auth::user();

        if ($role !== $currentRole && ! $actor?->can('roles.assign')) {
            throw new AuthorizationException(__('users.messages.cannot_assign_roles'));
        }

        if ($role === RoleName::SystemAdmin->value && ! $actor?->hasRole(RoleName::SystemAdmin->value)) {
            throw new AuthorizationException(__('users.messages.cannot_assign_admin'));
        }
    }
}

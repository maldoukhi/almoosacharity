<?php

namespace App\Actions\Users;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;

class UpdateUser
{
    /**
     * Update the given user's attributes and resync their single role. The
     * password is optional: an empty/missing value leaves it unchanged.
     *
     * This is the source of truth for two security-sensitive checks that
     * are also performed (for UX) in the Livewire Form, but must be
     * re-verified here since the form-level check is bypassable:
     * - Role assignment/change requires `roles.assign`, and the
     *   `system-admin` role may only be assigned by a system-admin actor
     *   (see AuthorizeRoleAssignment).
     * - Changing a user's status requires the `suspend` ability, which
     *   (via UserPolicy + the Gate::before self-protection carve-out) also
     *   blocks an actor from suspending themselves.
     *
     * @param  array{name: string, email: string, phone?: ?string, job_title?: ?string, password?: ?string, status: string, preferred_locale: string, role: string}  $data
     */
    public function handle(User $user, array $data): User
    {
        app(AuthorizeRoleAssignment::class)->handle($data['role'], $user->roles->first()?->name);

        if ($data['status'] !== $user->status->value) {
            Gate::authorize('suspend', $user);
        }

        return DB::transaction(function () use ($user, $data): User {
            $attributes = [
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'job_title' => $data['job_title'] ?? null,
                'status' => $data['status'],
                'preferred_locale' => $data['preferred_locale'],
            ];

            if (! empty($data['password'])) {
                $attributes['password'] = Hash::make($data['password']);
            }

            $user->update($attributes);

            $user->syncRoles([$data['role']]);

            return $user->fresh();
        });
    }
}

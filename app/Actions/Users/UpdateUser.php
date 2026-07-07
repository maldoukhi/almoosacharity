<?php

namespace App\Actions\Users;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UpdateUser
{
    /**
     * Update the given user's attributes and resync their single role. The
     * password is optional: an empty/missing value leaves it unchanged.
     *
     * @param  array{name: string, email: string, phone?: ?string, job_title?: ?string, password?: ?string, status: string, preferred_locale: string, role: string}  $data
     */
    public function handle(User $user, array $data): User
    {
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

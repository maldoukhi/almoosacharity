<?php

namespace App\Actions\Users;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CreateUser
{
    /**
     * Create a new user with the given validated attributes and assign the
     * single given role.
     *
     * @param  array{name: string, email: string, phone?: ?string, job_title?: ?string, password: string, status: string, preferred_locale: string, role: string}  $data
     */
    public function handle(array $data): User
    {
        app(AuthorizeRoleAssignment::class)->handle($data['role'], null);

        return DB::transaction(function () use ($data): User {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'job_title' => $data['job_title'] ?? null,
                'password' => Hash::make($data['password']),
                'status' => $data['status'],
                'preferred_locale' => $data['preferred_locale'],
            ]);

            $user->syncRoles([$data['role']]);

            return $user;
        });
    }
}

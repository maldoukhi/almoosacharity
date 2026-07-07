<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Outside local/testing, the initial password must come from
     * ADMIN_INITIAL_PASSWORD — seeding a privileged account with a
     * well-known password in production would leave it open to anyone.
     * An existing admin is never overwritten (firstOrCreate).
     */
    public function run(): void
    {
        $password = env('ADMIN_INITIAL_PASSWORD')
            ?: (app()->environment('local', 'testing') ? 'password' : null);

        if (blank($password)) {
            throw new RuntimeException('ADMIN_INITIAL_PASSWORD must be set to seed the admin user outside local environments.');
        }

        $admin = User::query()->firstOrCreate(
            ['email' => 'admin@almoosacharity.org'],
            [
                'name' => 'مدير النظام',
                'password' => Hash::make($password),
                'status' => UserStatus::Active,
                'email_verified_at' => now(),
            ]
        );

        $admin->assignRole(RoleName::SystemAdmin->value);
    }
}

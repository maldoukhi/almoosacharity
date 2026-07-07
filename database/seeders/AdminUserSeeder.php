<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Note: the password below is for the local/demo environment only.
     */
    public function run(): void
    {
        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@almoosacharity.org'],
            [
                'name' => 'مدير النظام',
                'password' => Hash::make('password'),
                'status' => UserStatus::Active,
                'email_verified_at' => now(),
            ]
        );

        $admin->assignRole(RoleName::SystemAdmin->value);
    }
}

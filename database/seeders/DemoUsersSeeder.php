<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoUsersSeeder extends Seeder
{
    /**
     * Demo users for each role beyond the admin.
     */
    public function run(): void
    {
        $users = [
            [
                'email' => 'researcher@almoosacharity.org',
                'name' => 'محمد علي الشمراني',
                'role' => RoleName::SocialResearcher->value,
            ],
            [
                'email' => 'manager@almoosacharity.org',
                'name' => 'فهد عبدالله القحطاني',
                'role' => RoleName::Manager->value,
            ],
            [
                'email' => 'dataentry@almoosacharity.org',
                'name' => 'سارة محمد العتيبي',
                'role' => RoleName::DataEntry->value,
            ],
        ];

        foreach ($users as $userData) {
            $user = User::query()->updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => Hash::make('password'),
                    'status' => UserStatus::Active,
                    'email_verified_at' => now(),
                ],
            );

            $user->assignRole($userData['role']);
        }
    }
}

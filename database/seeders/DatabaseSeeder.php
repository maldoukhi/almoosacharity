<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $registrar = app(PermissionRegistrar::class);

        // The permission cache is forgotten between each seeder so newly
        // created permissions/roles are visible to the following seeder.
        $registrar->forgetCachedPermissions();
        $this->call(PermissionSeeder::class);

        $registrar->forgetCachedPermissions();
        $this->call(RoleSeeder::class);

        $registrar->forgetCachedPermissions();
        $this->call(AdminUserSeeder::class);

        $registrar->forgetCachedPermissions();
        $this->call(DemoUsersSeeder::class);

        $registrar->forgetCachedPermissions();
        $this->call(ApprovalFlowSeeder::class);
        $this->call(AidProgramSeeder::class);
        $this->call(BeneficiaryCategorySeeder::class);

        // Only seed demo data in local environment
        if (app()->environment('local')) {
            $this->call(DemoDataSeeder::class);
        }
    }
}

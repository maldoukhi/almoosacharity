<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // system-admin: no direct permissions - relies entirely on the
        // Gate::before bypass registered in AppServiceProvider.
        Role::findOrCreate(RoleName::SystemAdmin->value, 'web');

        $socialResearcher = Role::findOrCreate(RoleName::SocialResearcher->value, 'web');
        $socialResearcher->syncPermissions([
            'beneficiaries.view',
            'beneficiaries.create',
            'beneficiaries.update',
            'beneficiaries.export',
            'aids.view',
            'aids.create',
            'aids.update',
            'aids.submit',
            'approvals.view',
            'approvals.act',
            'surveys.view',
            'surveys.results.view',
            'reports.view',
        ]);

        $dataEntry = Role::findOrCreate(RoleName::DataEntry->value, 'web');
        $dataEntry->syncPermissions([
            'beneficiaries.view',
            'beneficiaries.create',
            'beneficiaries.update',
            'beneficiaries.import',
            'beneficiaries.bank-data.manage',
            'aids.view',
            'aids.create',
        ]);
    }
}

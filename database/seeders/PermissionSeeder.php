<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * All permissions used across the application, guard 'web'.
     *
     * @var array<int, string>
     */
    protected array $permissions = [
        // Users
        'users.view',
        'users.create',
        'users.update',
        'users.delete',
        'users.suspend',

        // Roles
        'roles.view',
        'roles.create',
        'roles.update',
        'roles.delete',
        'roles.assign',

        // Beneficiaries
        'beneficiaries.view',
        'beneficiaries.create',
        'beneficiaries.update',
        'beneficiaries.delete',
        'beneficiaries.restore',
        'beneficiaries.import',
        'beneficiaries.export',
        'beneficiaries.bank-data.view',
        'beneficiaries.bank-data.manage',

        // Aids
        'aids.view',
        'aids.view-any',
        'aids.create',
        'aids.update',
        'aids.delete',
        'aids.submit',
        'aids.export',

        // Approvals
        'approvals.view',
        'approvals.act',
        'approvals.configure',

        // Disbursements
        'disbursements.view',
        'disbursements.manage',
        'disbursements.confirm',

        // Surveys
        'surveys.view',
        'surveys.manage',
        'surveys.results.view',

        // Settings
        'settings.view',
        'settings.manage',

        // Reports
        'reports.view',
        'reports.export',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ($this->permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
    }
}

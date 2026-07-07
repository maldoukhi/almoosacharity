<?php

namespace App\Actions\Roles;

use App\Enums\RoleName;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use InvalidArgumentException;

class UpdateRole
{
    /**
     * Update a role's name and resync its permissions. The three default
     * system roles may never be renamed (their permission sets may still be
     * changed) — the Livewire form is expected to guard against this in the
     * UI, but this is enforced here too as the source of truth.
     *
     * @param  array{name: string, permissions: array<int, string>}  $data
     */
    public function handle(Role $role, array $data): Role
    {
        $defaultRoleNames = array_column(RoleName::cases(), 'value');

        if (in_array($role->name, $defaultRoleNames, true) && $data['name'] !== $role->name) {
            throw new InvalidArgumentException('Default system roles cannot be renamed.');
        }

        return DB::transaction(function () use ($role, $data): Role {
            $role->update(['name' => $data['name']]);
            $role->syncPermissions($data['permissions'] ?? []);

            return $role->fresh();
        });
    }
}

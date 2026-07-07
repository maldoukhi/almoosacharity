<?php

use App\Enums\RoleName;
use App\Livewire\Admin\Roles\Form as RoleForm;
use App\Livewire\Admin\Roles\Index as RoleIndex;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

it('lets an admin create a new role and syncs the selected permissions', function () {
    asAdmin();

    Livewire::test(RoleForm::class)
        ->set('name', 'auditor')
        ->set('selectedPermissions', ['reports.view', 'reports.export'])
        ->call('save')
        ->assertRedirect(route('admin.roles.index'));

    $role = Role::where('name', 'auditor')->firstOrFail();

    expect($role->permissions->pluck('name')->sort()->values()->all())
        ->toBe(['reports.export', 'reports.view']);
});

it('refuses to delete one of the three default system roles', function () {
    asAdmin();

    $role = Role::where('name', RoleName::DataEntry->value)->firstOrFail();

    Livewire::test(RoleIndex::class)
        ->call('delete', $role->id)
        ->assertDispatched('toast', type: 'error');

    expect(Role::find($role->id))->not->toBeNull();
});

it('refuses to delete a role that still has users assigned to it', function () {
    asAdmin();

    $role = Role::create(['name' => 'temp-role', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole($role->name);

    Livewire::test(RoleIndex::class)
        ->call('delete', $role->id)
        ->assertDispatched('toast', type: 'error');

    expect(Role::find($role->id))->not->toBeNull();
});

it('deletes a custom role that has no users assigned', function () {
    asAdmin();

    $role = Role::create(['name' => 'temp-role-unused', 'guard_name' => 'web']);

    Livewire::test(RoleIndex::class)
        ->call('delete', $role->id)
        ->assertDispatched('toast', type: 'success');

    expect(Role::find($role->id))->toBeNull();
});

it('refuses to rename one of the three default system roles', function () {
    asAdmin();

    $role = Role::where('name', RoleName::SocialResearcher->value)->firstOrFail();

    Livewire::test(RoleForm::class, ['role' => $role])
        ->set('name', 'renamed-role')
        ->set('selectedPermissions', ['approvals.view'])
        ->call('save')
        ->assertHasErrors(['name']);

    expect($role->fresh()->name)->toBe(RoleName::SocialResearcher->value);
});

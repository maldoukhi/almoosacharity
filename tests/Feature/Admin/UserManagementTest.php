<?php

use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Livewire\Admin\Users\Form as UserForm;
use App\Livewire\Admin\Users\Index as UserIndex;
use App\Models\User;
use Livewire\Livewire;

it('lets an admin create a user with a role and see them in the table with that role', function () {
    asAdmin();

    Livewire::test(UserForm::class)
        ->set('name', 'Sara Al-Otaibi')
        ->set('email', 'sara@example.com')
        ->set('phone', '0501234567')
        ->set('job_title', 'Case Worker')
        ->set('role', RoleName::DataEntry->value)
        ->set('status', 'active')
        ->set('preferred_locale', 'ar')
        ->set('password', 'super-secret-1')
        ->set('password_confirmation', 'super-secret-1')
        ->call('save')
        ->assertRedirect(route('admin.users.index'));

    $created = User::where('email', 'sara@example.com')->firstOrFail();

    expect($created->name)->toBe('Sara Al-Otaibi');
    expect($created->hasRole(RoleName::DataEntry->value))->toBeTrue();

    Livewire::test(UserIndex::class)
        ->assertSee('Sara Al-Otaibi')
        ->assertSee(RoleName::DataEntry->label());
});

it('updates a user without changing their password when the password field is left empty', function () {
    asAdmin();

    $target = User::factory()->create([
        'name' => 'Old Name',
        'status' => UserStatus::Active,
        'preferred_locale' => \App\Enums\Locale::Ar,
    ]);
    $target->assignRole(RoleName::DataEntry->value);
    $originalPasswordHash = $target->password;

    Livewire::test(UserForm::class, ['user' => $target])
        ->set('name', 'New Name')
        ->set('email', $target->email)
        ->set('role', RoleName::SocialResearcher->value)
        ->set('status', 'active')
        ->set('preferred_locale', 'ar')
        ->set('password', '')
        ->set('password_confirmation', '')
        ->call('save')
        ->assertRedirect(route('admin.users.index'));

    $target->refresh();

    expect($target->name)->toBe('New Name');
    expect($target->password)->toBe($originalPasswordHash);
    expect($target->hasRole(RoleName::SocialResearcher->value))->toBeTrue();
    expect($target->hasRole(RoleName::DataEntry->value))->toBeFalse();
});

it('lets an admin toggle another user status between active and suspended', function () {
    asAdmin();

    $target = User::factory()->create(['status' => UserStatus::Active]);

    Livewire::test(UserIndex::class)->call('toggleStatus', $target->id);

    expect($target->fresh()->status)->toBe(UserStatus::Suspended);

    Livewire::test(UserIndex::class)->call('toggleStatus', $target->id);

    expect($target->fresh()->status)->toBe(UserStatus::Active);
});

it('lets an admin soft delete a user', function () {
    asAdmin();

    $target = User::factory()->create(['status' => UserStatus::Active]);

    Livewire::test(UserIndex::class)->call('delete', $target->id);

    expect(User::find($target->id))->toBeNull();
    expect(User::withTrashed()->find($target->id))->not->toBeNull();
    expect(User::withTrashed()->find($target->id)->trashed())->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| Self-protection (UserPolicy::suspend/delete)
|--------------------------------------------------------------------------
|
| UserPolicy explicitly blocks a user from suspending/deleting their own
| account, but Gate::before grants system-admin an unconditional pass on
| every ability (see AppServiceProvider::boot()), so it bypasses this
| policy entirely for admins. These tests therefore use a custom
| non-admin role holding users.suspend/users.delete directly, which is
| the only way to exercise the self-protection check in UserPolicy.
| See the final report for the system-admin self-suspend/self-delete gap.
*/
it('forbids a non-admin user manager from suspending their own account', function () {
    seedRolesAndPermissions();

    $role = \Spatie\Permission\Models\Role::create(['name' => 'user-manager', 'guard_name' => 'web']);
    $role->syncPermissions(['users.view', 'users.suspend']);

    $manager = User::factory()->create(['status' => UserStatus::Active]);
    $manager->assignRole($role->name);
    $this->actingAs($manager);

    Livewire::test(UserIndex::class)
        ->call('toggleStatus', $manager->id)
        ->assertForbidden();

    expect($manager->fresh()->status)->toBe(UserStatus::Active);
});

it('forbids a non-admin user manager from deleting their own account', function () {
    seedRolesAndPermissions();

    $role = \Spatie\Permission\Models\Role::create(['name' => 'user-manager', 'guard_name' => 'web']);
    $role->syncPermissions(['users.view', 'users.delete']);

    $manager = User::factory()->create(['status' => UserStatus::Active]);
    $manager->assignRole($role->name);
    $this->actingAs($manager);

    Livewire::test(UserIndex::class)
        ->call('delete', $manager->id)
        ->assertForbidden();

    expect(User::find($manager->id))->not->toBeNull();
});

it('forbids a user without the users.create permission from opening the create user form', function () {
    asDataEntry();

    Livewire::test(UserForm::class)->assertForbidden();
});

it('forbids a user without the users.update permission from opening the edit user form', function () {
    asResearcher();

    $target = User::factory()->create(['status' => UserStatus::Active]);

    Livewire::test(UserForm::class, ['user' => $target])->assertForbidden();
});

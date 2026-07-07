<?php

use App\Enums\Locale;
use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Livewire\Admin\Users\Form as UserForm;
use App\Livewire\Admin\Users\Index as UserIndex;
use App\Livewire\Auth\ForgotPassword;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/*
|--------------------------------------------------------------------------
| Regression tests for the phase 1 security review
|--------------------------------------------------------------------------
|
| C1 — role assignment can no longer be used to escalate privileges: role
|      changes require the roles.assign permission, and only an actual
|      system-admin may ever hand out the system-admin role.
| C2 — Gate::before's admin bypass no longer covers delete/suspend against
|      the acting user's own account, and Users\Index double-checks this
|      before even calling Gate::authorize().
| C3 — changing a user's status from the edit form requires the suspend
|      ability, same as the Index toggle action.
| T1 — forgot-password never reveals whether an email is registered.
| T3 — a suspended user's live session is terminated on their next request.
*/

it('forbids a user manager without roles.assign from creating a user with the system-admin role', function () {
    seedRolesAndPermissions();

    $role = Role::create(['name' => 'user-editor', 'guard_name' => 'web']);
    $role->syncPermissions(['users.view', 'users.create', 'users.update']);

    $editor = User::factory()->create(['status' => UserStatus::Active]);
    $editor->assignRole($role->name);
    test()->actingAs($editor);

    Livewire::test(UserForm::class)
        ->set('name', 'Malicious Admin')
        ->set('email', 'malicious-admin@example.com')
        ->set('role', RoleName::SystemAdmin->value)
        ->set('status', 'active')
        ->set('preferred_locale', 'ar')
        ->set('password', 'super-secret-1')
        ->set('password_confirmation', 'super-secret-1')
        ->call('save')
        ->assertHasErrors(['role']);

    expect(User::where('email', 'malicious-admin@example.com')->exists())->toBeFalse();
});

it('forbids the same user manager from changing an existing user\'s role', function () {
    seedRolesAndPermissions();

    $role = Role::create(['name' => 'user-editor-update', 'guard_name' => 'web']);
    $role->syncPermissions(['users.view', 'users.create', 'users.update']);

    $editor = User::factory()->create(['status' => UserStatus::Active]);
    $editor->assignRole($role->name);
    test()->actingAs($editor);

    $target = User::factory()->create(['status' => UserStatus::Active, 'preferred_locale' => Locale::Ar]);
    $target->assignRole(RoleName::DataEntry->value);

    Livewire::test(UserForm::class, ['user' => $target])
        ->set('role', RoleName::SocialResearcher->value)
        ->call('save')
        ->assertHasErrors(['role']);

    expect($target->fresh()->hasRole(RoleName::DataEntry->value))->toBeTrue();
    expect($target->fresh()->hasRole(RoleName::SocialResearcher->value))->toBeFalse();
});

it('prevents a system admin from suspending or deleting their own account via the Index component', function () {
    $admin = asAdmin();

    Livewire::test(UserIndex::class)
        ->call('toggleStatus', $admin->id)
        ->assertDispatched('toast', type: 'error');

    expect($admin->fresh()->status)->toBe(UserStatus::Active);

    Livewire::test(UserIndex::class)
        ->call('delete', $admin->id)
        ->assertDispatched('toast', type: 'error');

    expect(User::withTrashed()->find($admin->id))->not->toBeNull();
    expect($admin->fresh()->trashed())->toBeFalse();
});

it('forbids a user manager with only users.update from changing another user\'s status via the form', function () {
    seedRolesAndPermissions();

    $role = Role::create(['name' => 'status-blocked-editor', 'guard_name' => 'web']);
    $role->syncPermissions(['users.view', 'users.update']);

    $editor = User::factory()->create(['status' => UserStatus::Active]);
    $editor->assignRole($role->name);
    test()->actingAs($editor);

    $target = User::factory()->create(['status' => UserStatus::Active, 'preferred_locale' => Locale::Ar]);
    $target->assignRole(RoleName::DataEntry->value);

    Livewire::test(UserForm::class, ['user' => $target])
        ->set('status', 'suspended')
        ->call('save')
        ->assertForbidden();

    expect($target->fresh()->status)->toBe(UserStatus::Active);
});

it('logs a suspended user out and redirects them to login when their live session hits an authenticated route', function () {
    $user = User::factory()->create(['status' => UserStatus::Active]);
    test()->actingAs($user);

    $user->update(['status' => UserStatus::Suspended]);

    test()->get('/dashboard')->assertRedirect(route('login'));

    test()->assertGuest();
});

it('shows the same generic success message for forgot-password regardless of whether the email is registered', function () {
    $component = Livewire::test(ForgotPassword::class)
        ->set('email', 'nobody-at-all@example.com')
        ->call('sendResetLink');

    $component->assertHasNoErrors();

    expect($component->get('status'))->toBe(__('passwords.sent'));
});

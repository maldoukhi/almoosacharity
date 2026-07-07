<?php

use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;

pest()->extend(Tests\TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| RBAC test helpers
|--------------------------------------------------------------------------
|
| Phase 1 (auth/roles/users) tests need the permission + role catalog to
| exist before assigning roles to users. These helpers run the real
| seeders (idempotently, via findOrCreate) and forget the Spatie
| permission cache between each step exactly like DatabaseSeeder does,
| so tests never drift from the production seeding sequence.
*/

/**
 * Seed the full permission + role catalog for the current test.
 */
function seedRolesAndPermissions(): void
{
    $registrar = app(PermissionRegistrar::class);

    $registrar->forgetCachedPermissions();
    (new PermissionSeeder)->run();

    $registrar->forgetCachedPermissions();
    (new RoleSeeder)->run();

    $registrar->forgetCachedPermissions();
}

/**
 * Create an active user assigned to the given role. Does not log the user
 * in — use the as*() helpers below for that.
 */
function userWithRole(RoleName $role, array $attributes = []): User
{
    seedRolesAndPermissions();

    $user = User::factory()->create(array_merge(
        ['status' => UserStatus::Active],
        $attributes,
    ));

    $user->assignRole($role->value);

    return $user;
}

/**
 * Create a system-admin user, log them in for the current test, and
 * return the model.
 */
function asAdmin(array $attributes = []): User
{
    $user = userWithRole(RoleName::SystemAdmin, $attributes);

    test()->actingAs($user);

    return $user;
}

/**
 * Create a social-researcher user, log them in for the current test, and
 * return the model.
 */
function asResearcher(array $attributes = []): User
{
    $user = userWithRole(RoleName::SocialResearcher, $attributes);

    test()->actingAs($user);

    return $user;
}

/**
 * Create a data-entry user, log them in for the current test, and return
 * the model.
 */
function asDataEntry(array $attributes = []): User
{
    $user = userWithRole(RoleName::DataEntry, $attributes);

    test()->actingAs($user);

    return $user;
}

<?php

use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\User;
use Database\Seeders\AidProgramSeeder;
use Database\Seeders\ApprovalFlowSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
 * Unit tests (Rules/Enums/...) need the Laravel container booted so that
 * facades and the __() translation helper work when a ValidationRule
 * fails, but never touch the database, so they skip RefreshDatabase.
 */
pest()->extend(TestCase::class)
    ->in('Unit');

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

/**
 * Create a manager user, log them in for the current test, and return the
 * model.
 */
function asManager(array $attributes = []): User
{
    $user = userWithRole(RoleName::Manager, $attributes);

    test()->actingAs($user);

    return $user;
}

/*
|--------------------------------------------------------------------------
| Saudi IBAN test fixture
|--------------------------------------------------------------------------
|
| SA0380000000608010167519 is the canonical example IBAN (Wikipedia's IBAN
| article) and has a real, valid ISO 13616 (MOD-97) checksum, unlike
| BeneficiaryFactory::generateValidIban() (see security review notes:
| that factory helper's checksum computation is missing the "00" check-
| digit placeholder mandated by the algorithm and produces IBANs that
| fail App\Rules\SaudiIban's own checksum check). Tests that need a
| genuinely valid IBAN should use this fixture instead of the factory's
| default 'hasIban' state.
*/

/**
 * A syntactically and cryptographically valid Saudi IBAN, safe to use
 * anywhere App\Rules\SaudiIban is expected to pass.
 */
function validSaudiIban(): string
{
    return 'SA0380000000608010167519';
}

/*
|--------------------------------------------------------------------------
| Beneficiary / Aid form data fixtures
|--------------------------------------------------------------------------
*/

/**
 * A minimal set of attributes satisfying every rule in
 * App\Livewire\Beneficiaries\Form::save(), keyed by the component's public
 * property names so it can be spread directly into Livewire::test(...)
 * ->set($field, $value) calls or ->set($this->validBeneficiaryFormData()).
 *
 * @return array<string, mixed>
 */
function validBeneficiaryFormData(array $overrides = []): array
{
    return array_merge([
        'first_name' => 'محمد',
        'second_name' => 'عبدالله',
        'third_name' => '',
        'last_name' => 'العتيبي',
        'id_type' => 'national_id',
        'national_id' => '1'.random_int(100000000, 999999999),
        'nationality' => 'SA',
        'birth_date' => '1990-01-01',
        'gender' => 'male',
        'mobile' => '05'.random_int(10000000, 99999999),
        'marital_status' => 'married',
        'family_members_count' => 4,
        'occupation' => 'موظف',
        'employer' => 'القطاع الحكومي',
        'monthly_income' => 3000,
        'health_status' => 'سليم',
        'special_needs' => '',
        'housing_type' => 'owned',
        'rent_amount' => null,
        'national_address' => 'حي الملز، الرياض',
        'city' => 'الرياض',
        'district' => 'الملز',
        'status' => 'under_study',
        'selectedCategories' => [],
    ], $overrides);
}

/**
 * The same fixture shaped for direct Eloquent/Action creation (Beneficiary
 * model attributes rather than the Livewire form's property names) —
 * identical field set today, kept as a distinct helper so the two can
 * diverge without one silently breaking the other.
 *
 * @return array<string, mixed>
 */
function beneficiaryAttributes(array $overrides = []): array
{
    return validBeneficiaryFormData($overrides);
}

/*
|--------------------------------------------------------------------------
| Aid / approval flow catalog fixtures
|--------------------------------------------------------------------------
|
| Two factory-related bugs found while writing phase 2/3 tests, documented
| here since every Aid test has to work around them:
|
| 1. App\Models\Aid does not `use HasFactory`, even though
|    Database\Factories\AidFactory exists and is fully wired up (model
|    property, definition(), states). `Aid::factory()` therefore always
|    throws "Call to undefined method App\Models\Aid::factory()". Tests
|    must call `\Database\Factories\AidFactory::new(...)` directly instead
|    (this does not require the model's HasFactory trait).
| 2. AidFactory::definition() falls back to `AidProgram::factory()
|    ->create()` (and `Beneficiary::factory()->create()`) whenever no
|    matching row already exists in the database — but App\Models\AidProgram
|    (and ApprovalFlow) also do NOT use HasFactory, so that fallback would
|    itself throw a fatal error the moment AidFactory::new() is used against
|    an empty aid_programs table. Every Aid test must therefore seed the
|    real aid program + approval flow catalog first via seedAidCatalog()
|    below, so AidFactory's `inRandomOrder()->first() ?? ...::factory()
|    ->create()` fallback path is never actually exercised.
*/

/**
 * Seed the real aid program and default approval flow catalog (idempotent,
 * same seeders DatabaseSeeder runs in production).
 */
function seedAidCatalog(): void
{
    (new ApprovalFlowSeeder)->run();
    (new AidProgramSeeder)->run();
}

<?php

use App\Actions\Beneficiaries\CreateBeneficiary;
use App\Enums\AidProgramType;
use App\Enums\AidType;
use App\Enums\UserStatus;
use App\Livewire\GlobalSearch;
use App\Models\AidProgram;
use App\Models\Beneficiary;
use App\Models\User;
use Database\Factories\AidFactory;
use Livewire\Livewire;

it('returns a seeded beneficiary by national_id', function () {
    $actor = asResearcher();

    $beneficiary = app(CreateBeneficiary::class)->handle(
        beneficiaryAttributes([
            'national_id' => '1234567890',
            'first_name' => 'سارة',
            'last_name' => 'القحطاني',
        ]),
        [],
        $actor,
    );

    Livewire::test(GlobalSearch::class)
        ->set('query', '1234567890')
        ->assertSee(__('search.groups.beneficiaries'))
        ->assertSee($beneficiary->full_name)
        ->assertSee('1234567890');
});

it('returns a seeded aid by reference', function () {
    seedAidCatalog();
    asResearcher();

    $program = AidProgram::query()->where('type', AidProgramType::Cash)->firstOrFail();

    $aid = AidFactory::new()->draft()->create([
        'reference' => 'AID-SEARCH-000123',
        'title' => 'سلة رمضان',
        'beneficiary_id' => Beneficiary::factory()->create()->id,
        'aid_program_id' => $program->id,
        'type' => AidType::Cash,
        'amount' => 500,
    ]);

    Livewire::test(GlobalSearch::class)
        ->set('query', 'AID-SEARCH-000123')
        ->assertSee(__('search.groups.aids'))
        ->assertSee('AID-SEARCH-000123')
        ->assertSee('سلة رمضان');
});

it('gives a user without aids.view no aids group', function () {
    seedAidCatalog();
    seedRolesAndPermissions();

    // A plain authenticated user with no role/permissions assigned at
    // all (not even beneficiaries.view) — deliberately not one of the
    // seeded roles, all of which are granted aids.view in RoleSeeder.
    $actor = User::factory()->create(['status' => UserStatus::Active]);
    test()->actingAs($actor);

    $program = AidProgram::query()->where('type', AidProgramType::Cash)->firstOrFail();

    $aid = AidFactory::new()->draft()->create([
        'reference' => 'AID-NOPERM-000456',
        'beneficiary_id' => Beneficiary::factory()->create()->id,
        'aid_program_id' => $program->id,
        'type' => AidType::Cash,
        'amount' => 500,
    ]);

    Livewire::test(GlobalSearch::class)
        ->set('query', 'AID-NOPERM-000456')
        ->assertDontSee('AID-NOPERM-000456')
        ->assertDontSee(__('search.groups.aids'))
        ->assertSee(__('search.empty'));
});

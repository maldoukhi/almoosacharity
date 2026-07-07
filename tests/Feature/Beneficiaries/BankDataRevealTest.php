<?php

use App\Actions\Beneficiaries\CreateBeneficiary;
use App\Livewire\Beneficiaries\Profile\BankPanel;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

/**
 * beneficiaries.bank-data.view is not granted to any of the four seeded
 * roles, so a dedicated custom role is used to exercise the "has the
 * permission" side of RevealBankData.
 */
function userWithBankDataView(): \App\Models\User
{
    seedRolesAndPermissions();

    $role = Role::findOrCreate('bank-data-viewer', 'web');
    $role->syncPermissions(['beneficiaries.view', 'beneficiaries.bank-data.view']);

    $user = \App\Models\User::factory()->create(['status' => \App\Enums\UserStatus::Active]);
    $user->assignRole('bank-data-viewer');

    return $user;
}

it('lets a user with beneficiaries.bank-data.view reveal the iban and logs a bank-data-reveal activity entry', function () {
    $creator = asDataEntry();

    $beneficiary = app(CreateBeneficiary::class)->handle(
        beneficiaryAttributes([
            'iban' => validSaudiIban(),
            'bank_name' => 'بنك الرياض',
            'bank_account_holder' => 'محمد العتيبي',
        ]),
        [],
        $creator,
    );

    $viewer = userWithBankDataView();
    test()->actingAs($viewer);

    Livewire::test(BankPanel::class, ['beneficiary' => $beneficiary])
        ->call('reveal')
        ->assertSet('revealed', true)
        ->assertSet('ibanReveal', validSaudiIban())
        ->assertSet('holderReveal', 'محمد العتيبي');

    $activity = Activity::query()
        ->where('log_name', 'bank-data-reveal')
        ->where('subject_type', $beneficiary->getMorphClass())
        ->where('subject_id', $beneficiary->id)
        ->first();

    expect($activity)->not->toBeNull();
    expect($activity->causer_id)->toBe($viewer->id);
});

it('forbids a user without beneficiaries.bank-data.view from revealing bank data via BankPanel', function () {
    $creator = asDataEntry();

    $beneficiary = app(CreateBeneficiary::class)->handle(
        beneficiaryAttributes([
            'iban' => validSaudiIban(),
            'bank_name' => 'بنك الرياض',
            'bank_account_holder' => 'محمد العتيبي',
        ]),
        [],
        $creator,
    );

    // The social researcher role has beneficiaries.view but neither bank
    // data permission.
    asResearcher();

    Livewire::test(BankPanel::class, ['beneficiary' => $beneficiary])
        ->call('reveal')
        ->assertForbidden();

    expect(Activity::query()->where('log_name', 'bank-data-reveal')->exists())->toBeFalse();
});

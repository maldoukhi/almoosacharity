<?php

use App\Livewire\Beneficiaries\Form;
use App\Models\Beneficiary;
use Livewire\Livewire;

it('lets a data-entry user create a beneficiary with a valid iban', function () {
    asDataEntry();

    Livewire::test(Form::class)
        ->set(validBeneficiaryFormData([
            'bank_name' => 'بنك الرياض',
            'iban' => validSaudiIban(),
            'bank_account_holder' => 'محمد العتيبي',
        ]))
        ->call('save');

    $beneficiary = Beneficiary::query()->where('national_id', '!=', null)->latest('id')->first();

    expect($beneficiary)->not->toBeNull();
    expect($beneficiary->iban)->toBe(validSaudiIban());
    expect($beneficiary->bank_account_holder)->toBe('محمد العتيبي');
});

it('does not show the bank data tab to a social researcher, who lacks bank-data.manage', function () {
    asResearcher();

    Livewire::test(Form::class)
        ->assertSet('canManageBank', false)
        ->assertDontSee(__('beneficiaries.tab.bank'));
});

it('shows the bank data tab to a data-entry user, who holds bank-data.manage', function () {
    asDataEntry();

    Livewire::test(Form::class)
        ->assertSet('canManageBank', true)
        ->assertSee(__('beneficiaries.tab.bank'));
});

it('ignores an iban injected via Livewire set() by a social researcher (no bank-data.manage)', function () {
    asResearcher();

    Livewire::test(Form::class)
        ->set(validBeneficiaryFormData())
        // Attempted injection: the property exists on the component (bound
        // by wire:model when canManageBank is true) but is never rendered
        // for this actor, and canManageBank being false means the "iban"
        // rule is never added to the validated rule set either.
        ->set('iban', validSaudiIban())
        ->set('bank_account_holder', 'مستخدم مخترق')
        ->call('save');

    $beneficiary = Beneficiary::query()->latest('id')->first();

    expect($beneficiary)->not->toBeNull();
    expect($beneficiary->iban)->toBeNull();
    expect($beneficiary->bank_account_holder)->toBeNull();
});

it('rejects a duplicate national id', function () {
    asDataEntry();

    $existing = Beneficiary::factory()->create(['national_id' => '1122334455']);

    Livewire::test(Form::class)
        ->set(validBeneficiaryFormData(['national_id' => $existing->national_id]))
        ->call('save')
        ->assertHasErrors(['national_id']);
});

it('rejects a beneficiary form submission with an invalid iban checksum', function () {
    asDataEntry();

    Livewire::test(Form::class)
        ->set(validBeneficiaryFormData([
            'bank_name' => 'بنك الرياض',
            // Same shape as a valid IBAN, but the last digit is tampered
            // with so the MOD-97 checksum fails.
            'iban' => 'SA0380000000608010167510',
            'bank_account_holder' => 'محمد العتيبي',
        ]))
        ->call('save')
        ->assertHasErrors(['iban']);
});

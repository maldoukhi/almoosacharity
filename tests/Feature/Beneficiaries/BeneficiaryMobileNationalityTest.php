<?php

use App\Livewire\Beneficiaries\Form;
use App\Models\Beneficiary;
use Livewire\Livewire;

it('defaults a new beneficiary form to the Saudi nationality code', function () {
    asDataEntry();

    Livewire::test(Form::class)
        ->assertSet('nationality', 'SA');
});

it('normalizes a bare local mobile number (5XXXXXXXX) to 05XXXXXXXX on save', function () {
    asDataEntry();

    Livewire::test(Form::class)
        ->set(validBeneficiaryFormData(['mobile' => '512345678']))
        ->call('save')
        ->assertHasNoErrors();

    $beneficiary = Beneficiary::query()->latest('id')->first();

    expect($beneficiary->mobile)->toBe('0512345678');
});

it('normalizes an international mobile number (+9665XXXXXXXX) to 05XXXXXXXX on save', function () {
    asDataEntry();

    Livewire::test(Form::class)
        ->set(validBeneficiaryFormData(['mobile' => '+966512345678']))
        ->call('save')
        ->assertHasNoErrors();

    $beneficiary = Beneficiary::query()->latest('id')->first();

    expect($beneficiary->mobile)->toBe('0512345678');
});

it('normalizes an international mobile number with the 00 prefix (009665XXXXXXXX) to 05XXXXXXXX on save', function () {
    asDataEntry();

    Livewire::test(Form::class)
        ->set(validBeneficiaryFormData(['mobile' => '00966512345678']))
        ->call('save')
        ->assertHasNoErrors();

    $beneficiary = Beneficiary::query()->latest('id')->first();

    expect($beneficiary->mobile)->toBe('0512345678');
});

it('normalizes the field live via updatedMobile() as soon as it changes', function () {
    asDataEntry();

    Livewire::test(Form::class)
        ->set('mobile', '512345678')
        ->assertSet('mobile', '0512345678');
});

it('rejects a mobile number that cannot be normalized into a valid Saudi format', function () {
    asDataEntry();

    Livewire::test(Form::class)
        ->set(validBeneficiaryFormData(['mobile' => '0712345678']))
        ->call('save')
        ->assertHasErrors(['mobile']);
});

it('saves the selected nationality ISO code', function () {
    asDataEntry();

    Livewire::test(Form::class)
        ->set(validBeneficiaryFormData(['nationality' => 'EG']))
        ->call('save')
        ->assertHasNoErrors();

    $beneficiary = Beneficiary::query()->latest('id')->first();

    expect($beneficiary->nationality)->toBe('EG');
});

it('exposes the nationality picker options with Saudi Arabia first', function () {
    asDataEntry();

    $options = Livewire::test(Form::class)->get('countryOptions');

    expect(array_key_first($options))->toBe('SA');
    expect($options)->toHaveKey('EG');
});

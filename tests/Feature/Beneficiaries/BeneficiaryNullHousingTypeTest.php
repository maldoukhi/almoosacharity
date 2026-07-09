<?php

use App\Livewire\Beneficiaries\Form;
use App\Livewire\Beneficiaries\Show;
use App\Models\Beneficiary;
use Livewire\Livewire;

/**
 * Regression: housing_type used to be a NOT NULL enum column, so a blank
 * value (e.g. an imported beneficiary whose housing column wasn't mapped,
 * stored as '') threw a ValueError the moment the profile read the
 * attribute — 500-ing the "housing & income" tab and the edit form. It is
 * now nullable, and a null value must render everywhere without error.
 */
it('renders the housing & income tab for a beneficiary with no housing type', function () {
    asAdmin();

    $beneficiary = Beneficiary::factory()->create();
    // Force a null housing type at the DB level (the state an unmapped import
    // now produces).
    $beneficiary->forceFill(['housing_type' => null])->saveQuietly();

    Livewire::test(Show::class, ['beneficiary' => $beneficiary->fresh()])
        ->set('activeTab', 'housing_income')
        ->assertOk();
});

it('loads the edit form for a beneficiary with no housing type', function () {
    asAdmin();

    $beneficiary = Beneficiary::factory()->create();
    $beneficiary->forceFill(['housing_type' => null])->saveQuietly();

    Livewire::test(Form::class, ['beneficiary' => $beneficiary->fresh()])
        ->assertOk()
        ->assertSet('housing_type', '');
});

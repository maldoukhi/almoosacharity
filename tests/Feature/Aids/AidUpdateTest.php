<?php

use App\Enums\AidProgramType;
use App\Enums\AidStatus;
use App\Enums\AidType;
use App\Livewire\Aids\Form;
use App\Models\AidProgram;
use App\Models\Beneficiary;
use Database\Factories\AidFactory;
use Livewire\Livewire;

it('forbids editing an aid that is already under_review', function () {
    seedAidCatalog();
    // data-entry does not even hold aids.update; use social-researcher
    // (which does) so this test isolates the status-based rejection
    // (AidPolicy::update -> $aid->status->isEditable()) rather than a
    // missing-permission rejection.
    $creator = asResearcher();

    $program = AidProgram::query()->where('type', AidProgramType::Cash)->firstOrFail();

    $aid = AidFactory::new()->create([
        'beneficiary_id' => Beneficiary::factory()->create()->id,
        'aid_program_id' => $program->id,
        'type' => AidType::Cash,
        'status' => AidStatus::UnderReview,
        'amount' => 1000,
        'created_by' => $creator->id,
        'submitted_at' => now(),
    ]);

    Livewire::test(Form::class, ['aid' => $aid])->assertForbidden();

    expect($aid->fresh()->amount)->toEqual(1000);
});

it('lets a draft aid be edited and saved by its creator', function () {
    seedAidCatalog();
    $creator = asResearcher();

    $program = AidProgram::query()->where('type', AidProgramType::Cash)->firstOrFail();
    $beneficiary = Beneficiary::factory()->create();

    $aid = AidFactory::new()->draft()->create([
        'beneficiary_id' => $beneficiary->id,
        'aid_program_id' => $program->id,
        'type' => AidType::Cash,
        'amount' => 800,
        'purpose' => 'إعانة أولية',
        'created_by' => $creator->id,
    ]);

    Livewire::test(Form::class, ['aid' => $aid])
        ->set('amount', 1750)
        ->set('purpose', 'تحديث الغرض')
        ->call('save');

    expect($aid->fresh()->amount)->toEqual(1750);
    expect($aid->fresh()->purpose)->toBe('تحديث الغرض');
    expect($aid->fresh()->status)->toBe(AidStatus::Draft);
});

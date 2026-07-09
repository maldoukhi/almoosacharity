<?php

use App\Enums\AidProgramType;
use App\Enums\AidStatus;
use App\Enums\AidType;
use App\Enums\BeneficiaryStatus;
use App\Livewire\Aids\Form;
use App\Models\Aid;
use App\Models\AidProgram;
use App\Models\Beneficiary;
use Livewire\Livewire;

it('walks the create wizard step by step and saves a draft at the end', function () {
    seedAidCatalog();
    $actor = asDataEntry();

    $program = AidProgram::query()->where('type', AidProgramType::Cash)->firstOrFail();
    $beneficiary = Beneficiary::factory()->create(['status' => BeneficiaryStatus::Active]);

    Livewire::test(Form::class)
        ->assertSet('step', 1)
        // Step 1 -> 2 needs at least one eligible beneficiary.
        ->set('beneficiary_ids', [$beneficiary->id])
        ->call('nextStep')
        ->assertSet('step', 2)
        // Step 2 -> 3 needs a program + type + amount.
        ->set('aid_program_id', $program->id)
        ->set('type', AidType::Cash->value)
        ->set('amount', 500)
        ->set('purpose', 'إيجار')
        ->call('nextStep')
        ->assertSet('step', 3)
        ->call('nextStep')
        ->assertSet('step', 4)
        // Save as draft from the review step.
        ->call('save')
        ->assertRedirect(route('aids.show', Aid::query()->where('purpose', 'إيجار')->firstOrFail()));

    $aid = Aid::query()->where('purpose', 'إيجار')->firstOrFail();
    expect($aid->status)->toBe(AidStatus::Draft);
    expect($aid->beneficiary_id)->toBe($beneficiary->id);
    expect($aid->created_by)->toBe($actor->id);
});

it('will not advance past step 1 without an eligible beneficiary', function () {
    seedAidCatalog();
    asDataEntry();

    Livewire::test(Form::class)
        ->set('beneficiary_ids', [])
        ->call('nextStep')
        ->assertHasErrors(['beneficiary_ids'])
        ->assertSet('step', 1);
});

it('will not advance past step 2 when the program is missing', function () {
    seedAidCatalog();
    asDataEntry();

    $beneficiary = Beneficiary::factory()->create(['status' => BeneficiaryStatus::Active]);

    Livewire::test(Form::class)
        ->set('beneficiary_ids', [$beneficiary->id])
        ->call('nextStep')
        ->assertSet('step', 2)
        ->set('aid_program_id', null)
        ->call('nextStep')
        ->assertHasErrors(['aid_program_id'])
        ->assertSet('step', 2);
});

it('a forward goToStep jump is blocked by an invalid intervening step', function () {
    seedAidCatalog();
    asDataEntry();

    Livewire::test(Form::class)
        ->set('beneficiary_ids', [])
        ->call('goToStep', 4)
        ->assertHasErrors(['beneficiary_ids'])
        ->assertSet('step', 1);
});

it('saving a draft directly still validates every step (calling save from step 1)', function () {
    seedAidCatalog();
    asDataEntry();

    $program = AidProgram::query()->where('type', AidProgramType::Cash)->firstOrFail();
    $beneficiary = Beneficiary::factory()->create(['status' => BeneficiaryStatus::Active]);

    // Even though the wizard is on step 1, save() must validate all input steps
    // and persist a complete form.
    Livewire::test(Form::class)
        ->set('beneficiary_ids', [$beneficiary->id])
        ->set('aid_program_id', $program->id)
        ->set('type', AidType::Cash->value)
        ->set('amount', 300)
        ->set('purpose', 'مسودة مباشرة')
        ->call('save')
        ->assertHasNoErrors();

    expect(Aid::query()->where('purpose', 'مسودة مباشرة')->where('status', AidStatus::Draft)->count())->toBe(1);
});

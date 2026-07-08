<?php

use App\Enums\AidProgramType;
use App\Enums\AidStatus;
use App\Enums\AidType;
use App\Livewire\Aids\Form;
use App\Models\Aid;
use App\Models\AidProgram;
use App\Models\Beneficiary;
use Livewire\Livewire;

it('creates one independent aid per selected beneficiary, sharing the same program/amount/notes', function () {
    seedAidCatalog();
    $actor = asDataEntry();

    $program = AidProgram::query()->where('type', AidProgramType::Cash)->firstOrFail();
    $beneficiaries = Beneficiary::factory()->count(3)->create();

    Livewire::test(Form::class)
        ->set('beneficiary_ids', $beneficiaries->pluck('id')->all())
        ->set('aid_program_id', $program->id)
        ->set('type', AidType::Cash->value)
        ->set('amount', 900)
        ->set('purpose', 'سلة غذائية شهرية')
        ->set('notes', 'دفعة جماعية')
        ->call('save')
        ->assertDispatched('toast', type: 'success', message: __('aids.messages.bulk_created', ['count' => 3]))
        ->assertRedirect(route('aids.index'));

    $created = Aid::query()->where('purpose', 'سلة غذائية شهرية')->get();

    expect($created)->toHaveCount(3);
    expect($created->pluck('beneficiary_id')->sort()->values()->all())
        ->toEqual($beneficiaries->pluck('id')->sort()->values()->all());

    foreach ($created as $aid) {
        expect($aid->amount)->toEqual(900);
        expect($aid->aid_program_id)->toBe($program->id);
        expect($aid->status)->toBe(AidStatus::Draft);
        expect($aid->created_by)->toBe($actor->id);
        expect($aid->notes)->toBe('دفعة جماعية');
    }
});

it('copies in-kind items to every aid created in a bulk submission', function () {
    seedAidCatalog();
    asDataEntry();

    $program = AidProgram::query()->where('type', AidProgramType::InKind)->firstOrFail();
    $beneficiaries = Beneficiary::factory()->count(2)->create();

    Livewire::test(Form::class)
        ->set('beneficiary_ids', $beneficiaries->pluck('id')->all())
        ->set('aid_program_id', $program->id)
        ->set('type', AidType::InKind->value)
        ->set('items', [
            ['name' => 'سلة غذائية', 'quantity' => 1, 'estimated_value' => 250, 'description' => ''],
        ])
        ->call('save')
        ->assertRedirect(route('aids.index'));

    $created = Aid::query()->where('aid_program_id', $program->id)->with('items')->get();

    expect($created)->toHaveCount(2);

    foreach ($created as $aid) {
        expect($aid->items)->toHaveCount(1);
        expect($aid->items->first()->name)->toBe('سلة غذائية');
    }
});

it('submits every bulk-created aid to under_review at the first stage', function () {
    seedAidCatalog();
    asDataEntry();

    $program = AidProgram::query()->where('type', AidProgramType::Cash)->firstOrFail();
    $beneficiaries = Beneficiary::factory()->count(3)->create();

    Livewire::test(Form::class)
        ->set('beneficiary_ids', $beneficiaries->pluck('id')->all())
        ->set('aid_program_id', $program->id)
        ->set('type', AidType::Cash->value)
        ->set('amount', 1100)
        ->set('purpose', 'دعم عاجل')
        ->call('saveAndSubmit')
        ->assertDispatched('toast', type: 'success', message: __('aids.messages.bulk_submitted', ['count' => 3]))
        ->assertRedirect(route('aids.index'));

    $created = Aid::query()->where('purpose', 'دعم عاجل')->get();

    expect($created)->toHaveCount(3);

    foreach ($created as $aid) {
        expect($aid->status)->toBe(AidStatus::UnderReview);
        expect($aid->submitted_at)->not->toBeNull();
        expect($aid->current_stage_id)->not->toBeNull();
    }
});

it('requires at least one beneficiary to be selected on create', function () {
    seedAidCatalog();
    asDataEntry();

    $program = AidProgram::query()->where('type', AidProgramType::Cash)->firstOrFail();

    Livewire::test(Form::class)
        ->set('beneficiary_ids', [])
        ->set('aid_program_id', $program->id)
        ->set('type', AidType::Cash->value)
        ->set('amount', 500)
        ->set('purpose', 'اختبار')
        ->call('save')
        ->assertHasErrors(['beneficiary_ids' => 'required']);

    expect(Aid::query()->where('purpose', 'اختبار')->count())->toBe(0);
});

it('adds and removes beneficiaries via the chip picker actions, only creating aids for the ones left selected', function () {
    seedAidCatalog();
    asDataEntry();

    $program = AidProgram::query()->where('type', AidProgramType::Cash)->firstOrFail();
    [$keep1, $keep2, $drop] = Beneficiary::factory()->count(3)->create();

    Livewire::test(Form::class)
        ->call('addBeneficiary', $keep1->id)
        ->call('addBeneficiary', $keep2->id)
        ->call('addBeneficiary', $drop->id)
        ->assertSet('beneficiary_ids', [$keep1->id, $keep2->id, $drop->id])
        ->call('removeBeneficiary', $drop->id)
        ->assertSet('beneficiary_ids', [$keep1->id, $keep2->id])
        ->set('aid_program_id', $program->id)
        ->set('type', AidType::Cash->value)
        ->set('amount', 400)
        ->set('purpose', 'رقائق الاختيار')
        ->call('save')
        ->assertRedirect(route('aids.index'));

    $created = Aid::query()->where('purpose', 'رقائق الاختيار')->get();

    expect($created)->toHaveCount(2);
    expect($created->pluck('beneficiary_id')->sort()->values()->all())
        ->toEqual(collect([$keep1->id, $keep2->id])->sort()->values()->all());
});

it('single-beneficiary create still redirects straight to the aid show page, not the index', function () {
    seedAidCatalog();
    asDataEntry();

    $beneficiary = Beneficiary::factory()->create();
    $program = AidProgram::query()->where('type', AidProgramType::Cash)->firstOrFail();

    Livewire::test(Form::class)
        ->set('beneficiary_ids', [$beneficiary->id])
        ->set('aid_program_id', $program->id)
        ->set('type', AidType::Cash->value)
        ->set('amount', 300)
        ->set('purpose', 'مستفيد واحد فقط')
        ->call('save')
        ->assertRedirect();

    $aid = Aid::query()->where('purpose', 'مستفيد واحد فقط')->firstOrFail();

    expect($aid->beneficiary_id)->toBe($beneficiary->id);
});

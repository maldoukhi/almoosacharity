<?php

use App\Enums\AidProgramType;
use App\Enums\AidStatus;
use App\Enums\AidType;
use App\Livewire\Aids\CancelAidModal;
use App\Livewire\Aids\Show;
use App\Livewire\Aids\SubmitAidModal;
use App\Models\Aid;
use App\Models\AidProgram;
use App\Models\ApprovalFlow;
use App\Models\Beneficiary;
use Database\Factories\AidFactory;
use Livewire\Livewire;

/**
 * Builds a draft cash aid owned by the given creator, ready to submit.
 */
function draftCashAid(int $creatorId, array $overrides = []): Aid
{
    seedAidCatalog();

    $program = AidProgram::query()->where('type', AidProgramType::Cash)->firstOrFail();
    $beneficiary = Beneficiary::factory()->create();

    return AidFactory::new()->draft()->create(array_merge([
        'beneficiary_id' => $beneficiary->id,
        'aid_program_id' => $program->id,
        'type' => AidType::Cash,
        'amount' => 1500,
        'created_by' => $creatorId,
    ], $overrides));
}

it('submits a draft aid into review through the submit modal', function () {
    $researcher = asResearcher();
    $aid = draftCashAid($researcher->id);

    Livewire::test(SubmitAidModal::class, ['aid' => $aid])
        ->call('confirm');

    $aid->refresh();

    expect($aid->status)->toBe(AidStatus::UnderReview);
    expect($aid->current_stage_id)->not->toBeNull();
    expect($aid->submitted_at)->not->toBeNull();
});

it('previews the first stage and its active reviewers in the submit modal', function () {
    $researcher = asResearcher();
    $aid = draftCashAid($researcher->id);

    // The first stage is the social-researcher stage, so the acting
    // researcher (the creator) is himself an active recipient of it.
    $component = Livewire::test(SubmitAidModal::class, ['aid' => $aid])
        ->assertOk();

    expect($component->instance()->firstStage())->not->toBeNull();
    expect($component->instance()->recipientCount())->toBeGreaterThan(0);
});

it('forbids opening the submit modal for an aid the actor did not create', function () {
    $researcher = asResearcher();
    $aid = draftCashAid($researcher->id);

    // A different researcher is not the creator, so the submit gate denies
    // them at mount and the modal never opens.
    asResearcher();

    Livewire::test(SubmitAidModal::class, ['aid' => $aid])
        ->assertForbidden();

    expect($aid->fresh()->status)->toBe(AidStatus::Draft);
});

it('hides the submit button for a system-admin on an already-under-review aid', function () {
    // A system-admin's Gate::before would otherwise pass the submit gate
    // regardless of state; the Show computeds must still hide submit.
    asAdmin();

    seedAidCatalog();
    $flow = ApprovalFlow::query()->default()->where('is_active', true)->firstOrFail();
    $stage = $flow->stages()->where('order', 1)->firstOrFail();
    $program = AidProgram::query()->where('type', AidProgramType::Cash)->firstOrFail();
    $beneficiary = Beneficiary::factory()->create();

    $aid = AidFactory::new()->create([
        'beneficiary_id' => $beneficiary->id,
        'aid_program_id' => $program->id,
        'type' => AidType::Cash,
        'status' => AidStatus::UnderReview,
        'approval_flow_id' => $flow->id,
        'current_stage_id' => $stage->id,
        'amount' => 1000,
        'submitted_at' => now(),
    ]);

    $component = Livewire::test(Show::class, ['aid' => $aid]);

    expect($component->instance()->canSubmit())->toBeFalse()
        ->and($component->instance()->canAct())->toBeTrue();
});

it('cancels a draft aid through the cancel modal', function () {
    $researcher = asResearcher();
    $aid = draftCashAid($researcher->id);

    Livewire::test(CancelAidModal::class, ['aid' => $aid])
        ->call('confirm');

    expect($aid->fresh()->status)->toBe(AidStatus::Cancelled);
});

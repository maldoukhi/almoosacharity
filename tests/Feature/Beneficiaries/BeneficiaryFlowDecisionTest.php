<?php

use App\Enums\BeneficiaryStatus;
use App\Livewire\Beneficiaries\BeneficiaryDecisionModal;
use App\Models\Beneficiary;
use App\Models\BeneficiaryDecision;
use App\Models\BeneficiaryFlow;
use Database\Seeders\BeneficiaryFlowSeeder;
use Livewire\Livewire;

/**
 * Seed the default two-stage beneficiary review flow (1 = social researcher,
 * 2 = manager) and return it.
 */
function seedBeneficiaryFlow(): BeneficiaryFlow
{
    (new BeneficiaryFlowSeeder)->run();

    return BeneficiaryFlow::query()->default()->where('is_active', true)->firstOrFail();
}

/**
 * Build a beneficiary under review, pinned at the given 1-based stage order
 * of the default flow.
 */
function underReviewBeneficiaryAtStage(int $stageOrder, array $overrides = []): Beneficiary
{
    $flow = seedBeneficiaryFlow();
    $stage = $flow->stages()->where('order', $stageOrder)->firstOrFail();

    return Beneficiary::factory()->create(array_merge([
        'status' => BeneficiaryStatus::UnderReview,
        'beneficiary_flow_id' => $flow->id,
        'current_stage_id' => $stage->id,
        'submitted_at' => now(),
        'decided_at' => null,
    ], $overrides));
}

it('advances a researcher approval at stage 1 to stage 2, keeping the beneficiary under_review', function () {
    asResearcher();
    $beneficiary = underReviewBeneficiaryAtStage(1);

    Livewire::test(BeneficiaryDecisionModal::class, ['beneficiary' => $beneficiary, 'action' => 'approve'])
        ->call('confirm');

    $beneficiary->refresh();
    $flow = BeneficiaryFlow::query()->default()->where('is_active', true)->firstOrFail();
    $secondStage = $flow->stages()->where('order', 2)->firstOrFail();

    expect($beneficiary->status)->toBe(BeneficiaryStatus::UnderReview);
    expect($beneficiary->current_stage_id)->toBe($secondStage->id);
});

it('lets the manager approve at the final stage, moving the beneficiary to active with decided_at set', function () {
    asManager();
    $beneficiary = underReviewBeneficiaryAtStage(2);

    Livewire::test(BeneficiaryDecisionModal::class, ['beneficiary' => $beneficiary, 'action' => 'approve'])
        ->call('confirm');

    $beneficiary->refresh();

    expect($beneficiary->status)->toBe(BeneficiaryStatus::Active);
    expect($beneficiary->current_stage_id)->toBeNull();
    expect($beneficiary->decided_at)->not->toBeNull();
});

it('rejects a beneficiary, with a note, into the final rejected status', function () {
    asManager();
    $beneficiary = underReviewBeneficiaryAtStage(2);

    Livewire::test(BeneficiaryDecisionModal::class, ['beneficiary' => $beneficiary, 'action' => 'reject'])
        ->set('note', 'البيانات غير مكتملة')
        ->call('confirm');

    $beneficiary->refresh();

    expect($beneficiary->status)->toBe(BeneficiaryStatus::Rejected);
    expect($beneficiary->current_stage_id)->toBeNull();

    $decision = BeneficiaryDecision::query()->where('beneficiary_id', $beneficiary->id)->latest('id')->firstOrFail();
    expect($decision->action->value)->toBe('reject');
    expect($decision->note)->toBe('البيانات غير مكتملة');
});

it('fails validation when rejecting without a note', function () {
    asManager();
    $beneficiary = underReviewBeneficiaryAtStage(2);

    Livewire::test(BeneficiaryDecisionModal::class, ['beneficiary' => $beneficiary, 'action' => 'reject'])
        ->set('note', '')
        ->call('confirm')
        ->assertHasErrors(['note']);

    expect($beneficiary->fresh()->status)->toBe(BeneficiaryStatus::UnderReview);
});

it('returns a beneficiary to new on a "return" decision, keeping the flow snapshot and note', function () {
    asResearcher();
    $beneficiary = underReviewBeneficiaryAtStage(1);
    $flowId = $beneficiary->beneficiary_flow_id;

    Livewire::test(BeneficiaryDecisionModal::class, ['beneficiary' => $beneficiary, 'action' => 'return'])
        ->set('note', 'الرجاء إرفاق تعريف بالراتب')
        ->call('confirm');

    $beneficiary->refresh();

    expect($beneficiary->status)->toBe(BeneficiaryStatus::New);
    expect($beneficiary->current_stage_id)->toBeNull();
    // The flow snapshot is kept for when the beneficiary is resubmitted.
    expect($beneficiary->beneficiary_flow_id)->toBe($flowId);

    $decision = BeneficiaryDecision::query()->where('beneficiary_id', $beneficiary->id)->latest('id')->firstOrFail();
    expect($decision->action->value)->toBe('return');
    expect($decision->note)->toBe('الرجاء إرفاق تعريف بالراتب');
});

it('forbids a data-entry user (no beneficiaries.review) from deciding at the researcher stage', function () {
    asDataEntry();
    $beneficiary = underReviewBeneficiaryAtStage(1);

    Livewire::test(BeneficiaryDecisionModal::class, ['beneficiary' => $beneficiary, 'action' => 'approve'])
        ->assertForbidden();

    expect($beneficiary->fresh()->status)->toBe(BeneficiaryStatus::UnderReview);
});

it('forbids a manager (wrong role for the stage) from deciding while still at the researcher stage', function () {
    asManager();
    $beneficiary = underReviewBeneficiaryAtStage(1);

    Livewire::test(BeneficiaryDecisionModal::class, ['beneficiary' => $beneficiary, 'action' => 'approve'])
        ->assertForbidden();

    expect($beneficiary->fresh()->status)->toBe(BeneficiaryStatus::UnderReview);
});

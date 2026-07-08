<?php

use App\Enums\AidProgramType;
use App\Enums\AidStatus;
use App\Enums\AidType;
use App\Livewire\Aids\ApprovalDecisionModal;
use App\Models\Aid;
use App\Models\AidProgram;
use App\Models\ApprovalDecision;
use App\Models\ApprovalFlow;
use App\Models\Beneficiary;
use Database\Factories\AidFactory;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;
use LivewireUI\Modal\Modal;

/**
 * Builds a cash aid under_review, pinned at the given 1-based stage order
 * of the default two-stage flow (1 = social researcher, 2 = manager).
 */
function underReviewAidAtStage(int $stageOrder, array $overrides = []): Aid
{
    seedAidCatalog();

    $flow = ApprovalFlow::query()->default()->where('is_active', true)->firstOrFail();
    $stage = $flow->stages()->where('order', $stageOrder)->firstOrFail();
    $program = AidProgram::query()->where('type', AidProgramType::Cash)->firstOrFail();
    $beneficiary = Beneficiary::factory()->create();

    return AidFactory::new()->create(array_merge([
        'beneficiary_id' => $beneficiary->id,
        'aid_program_id' => $program->id,
        'type' => AidType::Cash,
        'status' => AidStatus::UnderReview,
        'approval_flow_id' => $flow->id,
        'current_stage_id' => $stage->id,
        'amount' => 2000,
        'submitted_at' => now(),
        'decided_at' => null,
    ], $overrides));
}

/**
 * Regression guard for the "approve/reject gives 404" bug. The openModal
 * dispatch in aids/show.blade.php sends a scalar `aid` argument through
 * wire-elements/modal's own Modal::openModal() → resolveParameter(), which
 * resolves the `Aid $aid` mount parameter by calling
 * Aid::resolveRouteBinding($value) directly (bypassing Livewire's normal
 * hydration entirely — see vendor/wire-elements/modal/src/Modal.php). Aid
 * uses HasHashid, so resolveRouteBinding() decodes $value as a hashid: the
 * raw numeric id fails to decode and resolves to nothing (ModelNotFound),
 * which is exactly the 404 that shipped once before this was caught; only
 * the hashid (what the fixed blade now sends) resolves correctly.
 */
it('resolves the aid via its hashid through the real openModal() dispatch path, and fails on the raw numeric id', function () {
    $researcher = asResearcher();
    $aid = underReviewAidAtStage(1, ['created_by' => $researcher->id]);

    Livewire::test(Modal::class)
        ->call('openModal', 'aids.approval-decision-modal', ['aid' => $aid->getRouteKey(), 'action' => 'approve'])
        ->assertOk();

    expect(fn () => Livewire::test(Modal::class)
        ->call('openModal', 'aids.approval-decision-modal', ['aid' => (string) $aid->id, 'action' => 'approve']))
        ->toThrow(ModelNotFoundException::class);
});

it('advances a researcher approval at stage 1 to stage 2, keeping the aid under_review', function () {
    $researcher = asResearcher();
    $aid = underReviewAidAtStage(1, ['created_by' => $researcher->id]);

    Livewire::test(ApprovalDecisionModal::class, ['aid' => $aid, 'action' => 'approve'])
        ->call('confirm');

    $aid->refresh();
    $flow = ApprovalFlow::query()->default()->where('is_active', true)->firstOrFail();
    $secondStage = $flow->stages()->where('order', 2)->firstOrFail();

    expect($aid->status)->toBe(AidStatus::UnderReview);
    expect($aid->current_stage_id)->toBe($secondStage->id);
});

it('lets the manager approve at the final stage, moving the aid to approved with decided_at set', function () {
    $manager = asManager();
    $aid = underReviewAidAtStage(2);

    Livewire::test(ApprovalDecisionModal::class, ['aid' => $aid, 'action' => 'approve'])
        ->call('confirm');

    $aid->refresh();

    expect($aid->status)->toBe(AidStatus::Approved);
    expect($aid->current_stage_id)->toBeNull();
    expect($aid->decided_at)->not->toBeNull();
});

it('rejects an aid, with a note, into the final rejected status', function () {
    $manager = asManager();
    $aid = underReviewAidAtStage(2);

    Livewire::test(ApprovalDecisionModal::class, ['aid' => $aid, 'action' => 'reject'])
        ->set('note', 'المستندات غير مكتملة')
        ->call('confirm');

    $aid->refresh();

    expect($aid->status)->toBe(AidStatus::Rejected);
    expect($aid->current_stage_id)->toBeNull();

    $decision = ApprovalDecision::query()->where('aid_id', $aid->id)->latest('id')->firstOrFail();
    expect($decision->action->value)->toBe('reject');
    expect($decision->note)->toBe('المستندات غير مكتملة');
});

it('fails validation when rejecting without a note', function () {
    $manager = asManager();
    $aid = underReviewAidAtStage(2);

    Livewire::test(ApprovalDecisionModal::class, ['aid' => $aid, 'action' => 'reject'])
        ->set('note', '')
        ->call('confirm')
        ->assertHasErrors(['note']);

    expect($aid->fresh()->status)->toBe(AidStatus::UnderReview);
});

it('returns an aid to draft on a "return" decision, keeping the decision record and note', function () {
    $researcher = asResearcher();
    $aid = underReviewAidAtStage(1, ['created_by' => $researcher->id]);
    $flowId = $aid->approval_flow_id;

    Livewire::test(ApprovalDecisionModal::class, ['aid' => $aid, 'action' => 'return'])
        ->set('note', 'الرجاء إرفاق تعريف بالراتب')
        ->call('confirm');

    $aid->refresh();

    expect($aid->status)->toBe(AidStatus::Draft);
    expect($aid->current_stage_id)->toBeNull();
    // The flow snapshot is kept for when the aid is resubmitted.
    expect($aid->approval_flow_id)->toBe($flowId);

    $decision = ApprovalDecision::query()->where('aid_id', $aid->id)->latest('id')->firstOrFail();
    expect($decision->action->value)->toBe('return');
    expect($decision->note)->toBe('الرجاء إرفاق تعريف بالراتب');
});

it('forbids a data-entry user (no approvals.act) from deciding on an aid at the researcher stage', function () {
    $dataEntry = asDataEntry();
    $aid = underReviewAidAtStage(1);

    Livewire::test(ApprovalDecisionModal::class, ['aid' => $aid, 'action' => 'approve'])
        ->assertForbidden();

    expect($aid->fresh()->status)->toBe(AidStatus::UnderReview);
});

it('forbids a manager (wrong role for the stage) from deciding on an aid still at the researcher stage', function () {
    asManager();
    $aid = underReviewAidAtStage(1);

    Livewire::test(ApprovalDecisionModal::class, ['aid' => $aid, 'action' => 'approve'])
        ->assertForbidden();

    expect($aid->fresh()->status)->toBe(AidStatus::UnderReview);
});

it('forbids deciding on a draft aid that has no current stage', function () {
    $researcher = asResearcher();

    seedAidCatalog();
    $program = AidProgram::query()->where('type', AidProgramType::Cash)->firstOrFail();
    $beneficiary = Beneficiary::factory()->create();

    $aid = AidFactory::new()->draft()->create([
        'beneficiary_id' => $beneficiary->id,
        'aid_program_id' => $program->id,
        'type' => AidType::Cash,
        'amount' => 1000,
        'created_by' => $researcher->id,
    ]);

    Livewire::test(ApprovalDecisionModal::class, ['aid' => $aid, 'action' => 'approve'])
        ->assertForbidden();

    expect($aid->fresh()->status)->toBe(AidStatus::Draft);
});

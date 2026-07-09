<?php

use App\Actions\Aids\SubmitAid;
use App\Actions\Approvals\RecordApprovalDecision;
use App\Actions\Approvals\RequestBeneficiaryStageResponse;
use App\Enums\AidProgramType;
use App\Enums\AidStatus;
use App\Enums\AidType;
use App\Enums\ApprovalAction;
use App\Enums\ApprovalStageType;
use App\Livewire\Aids\Show;
use App\Livewire\Approvals\Inbox;
use App\Livewire\Settings\ApprovalFlows\Form;
use App\Models\Aid;
use App\Models\AidProgram;
use App\Models\ApprovalFlow;
use App\Models\ApprovalFlowStage;
use App\Models\Beneficiary;
use App\Models\BeneficiaryStageResponse;
use App\Models\MessageLog;
use Database\Factories\AidFactory;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

beforeEach(function () {
    config()->set('services.sms.driver', 'fake');
});

/**
 * Turn the default flow's stage $order into a beneficiary_response stage
 * and return [flow, stage1, stage2].
 *
 * @return array{0: ApprovalFlow, 1: ApprovalFlowStage, 2: ApprovalFlowStage}
 */
function flowWithBeneficiaryStage(int $order): array
{
    seedAidCatalog();

    $flow = ApprovalFlow::query()->default()->where('is_active', true)->firstOrFail();
    $stage1 = $flow->stages()->where('order', 1)->firstOrFail();
    $stage2 = $flow->stages()->where('order', 2)->firstOrFail();

    ($order === 1 ? $stage1 : $stage2)->update(['type' => ApprovalStageType::BeneficiaryResponse->value]);

    return [$flow, $stage1->fresh(), $stage2->fresh()];
}

function underReviewAidAt(ApprovalFlow $flow, ApprovalFlowStage $stage, ?Beneficiary $beneficiary = null): Aid
{
    $program = AidProgram::query()->where('type', AidProgramType::Cash)->firstOrFail();
    $beneficiary ??= Beneficiary::factory()->create();

    return AidFactory::new()->create([
        'beneficiary_id' => $beneficiary->id,
        'aid_program_id' => $program->id,
        'type' => AidType::Cash,
        'status' => AidStatus::UnderReview,
        'approval_flow_id' => $flow->id,
        'current_stage_id' => $stage->id,
        'amount' => 1500,
        'submitted_at' => now(),
        'decided_at' => null,
    ]);
}

it('offers only the two remaining stage types in the builder', function () {
    asAdmin();

    expect(ApprovalStageType::cases())->toHaveCount(2)
        ->and(collect(ApprovalStageType::cases())->pluck('value')->all())
        ->toBe(['approval', 'beneficiary_response']);

    $types = Livewire::test(Form::class)->instance()->stageTypes();

    expect(collect($types)->pluck('value')->all())->toBe(['approval', 'beneficiary_response']);
});

it('rewrites an existing document_upload stage to approval + documents_required', function () {
    seedAidCatalog();

    $flow = ApprovalFlow::query()->default()->firstOrFail();

    // Simulate a legacy row persisted before the type was removed.
    DB::table('approval_flow_stages')->where('id', $flow->stages()->first()->id)->update([
        'type' => 'document_upload',
        'documents_required' => false,
    ]);

    $migration = require base_path('database/migrations/2026_07_09_150000_rewrite_document_upload_stage_type.php');
    $migration->up();

    $stage = $flow->stages()->first()->fresh();

    expect($stage->type)->toBe(ApprovalStageType::Approval)
        ->and($stage->documents_required)->toBeTrue();
});

it('issues a beneficiary link and does not surface in the staff inbox when an approve advances into a beneficiary_response stage', function () {
    [$flow, $stage1, $stage2] = flowWithBeneficiaryStage(2);

    $researcher = asResearcher(); // holds stage 1's role
    $aid = underReviewAidAt($flow, $stage1);

    app(RecordApprovalDecision::class)->handle($aid, $researcher, ApprovalAction::Approve);

    $aid->refresh();

    // Advanced into the beneficiary_response stage, still under review.
    expect($aid->status)->toBe(AidStatus::UnderReview)
        ->and($aid->current_stage_id)->toBe($stage2->id);

    $response = BeneficiaryStageResponse::query()
        ->where('aid_id', $aid->id)
        ->where('approval_flow_stage_id', $stage2->id)
        ->first();

    expect($response)->not->toBeNull()
        ->and($response->sent_at)->not->toBeNull()
        ->and($response->responded_at)->toBeNull();

    // A link message was queued to the beneficiary.
    expect(MessageLog::query()->where('messageable_type', $aid->getMorphClass())->where('messageable_id', $aid->id)->exists())->toBeTrue();

    // The manager (stage 2's role) must NOT see it as an actionable inbox item.
    asManager();
    Livewire::test(Inbox::class)->assertDontSee($aid->reference);
});

it('issues the link when a submitted aid reaches a beneficiary_response first stage', function () {
    [$flow, $stage1] = flowWithBeneficiaryStage(1);

    $creator = asDataEntry();
    $program = AidProgram::query()->where('type', AidProgramType::Cash)->firstOrFail();
    $beneficiary = Beneficiary::factory()->create();

    $aid = AidFactory::new()->create([
        'beneficiary_id' => $beneficiary->id,
        'aid_program_id' => $program->id,
        'type' => AidType::Cash,
        'status' => AidStatus::Draft,
        'approval_flow_id' => null,
        'current_stage_id' => null,
        'amount' => 900,
        'created_by' => $creator->id,
    ]);

    app(SubmitAid::class)->handle($aid, $creator);

    $aid->refresh();

    expect($aid->status)->toBe(AidStatus::UnderReview)
        ->and($aid->current_stage_id)->toBe($stage1->id);

    expect(BeneficiaryStageResponse::query()->where('aid_id', $aid->id)->where('approval_flow_stage_id', $stage1->id)->exists())->toBeTrue();
});

it('shows the awaiting-beneficiary state with a working resend action on the aid page', function () {
    [$flow, , $stage2] = flowWithBeneficiaryStage(2);

    asAdmin();
    $aid = underReviewAidAt($flow, $stage2);

    // Seed the outstanding link record so the resend rotates it.
    app(RequestBeneficiaryStageResponse::class)->handle($aid, $stage2);

    $before = BeneficiaryStageResponse::query()->where('aid_id', $aid->id)->firstOrFail()->token_hash;
    $messagesBefore = MessageLog::query()->count();

    Livewire::test(Show::class, ['aid' => $aid])
        ->assertSee(__('approvals.beneficiary_response.awaiting_title'))
        // No approve/reject offered for a beneficiary_response stage.
        ->assertDontSee(__('approvals.action.approve'))
        ->call('resendBeneficiaryLink')
        ->assertHasNoErrors();

    $after = BeneficiaryStageResponse::query()->where('aid_id', $aid->id)->firstOrFail()->token_hash;

    // Token rotated and a fresh message was queued.
    expect($after)->not->toBe($before)
        ->and(MessageLog::query()->count())->toBeGreaterThan($messagesBefore);
});

<?php

use App\Enums\AidProgramType;
use App\Enums\AidStatus;
use App\Enums\AidType;
use App\Enums\ApprovalAction;
use App\Livewire\Approvals\Inbox;
use App\Models\Aid;
use App\Models\AidProgram;
use App\Models\ApprovalDecision;
use App\Models\ApprovalFlow;
use App\Models\ApprovalFlowStage;
use App\Models\Beneficiary;
use Database\Factories\AidFactory;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

/**
 * Build an under_review cash aid pinned at the default flow's first stage,
 * with a caller-controlled submission time.
 */
function slaAidAtStage1(ApprovalFlowStage $stage, Carbon $submittedAt): Aid
{
    $program = AidProgram::query()->where('type', AidProgramType::Cash)->firstOrFail();
    $beneficiary = Beneficiary::factory()->create();

    return AidFactory::new()->create([
        'beneficiary_id' => $beneficiary->id,
        'aid_program_id' => $program->id,
        'type' => AidType::Cash,
        'status' => AidStatus::UnderReview,
        'approval_flow_id' => $stage->approval_flow_id,
        'current_stage_id' => $stage->id,
        'amount' => 1500,
        'submitted_at' => $submittedAt,
        'decided_at' => null,
    ]);
}

function defaultStage1(): ApprovalFlowStage
{
    seedAidCatalog();

    $flow = ApprovalFlow::query()->default()->where('is_active', true)->firstOrFail();

    return $flow->stages()->where('order', 1)->firstOrFail();
}

afterEach(function () {
    Carbon::setTestNow();
});

it('detects overdue from the latest decision, falling back to submission time', function () {
    Carbon::setTestNow(Carbon::parse('2026-02-20 12:00:00'));

    $researcher = asResearcher();          // holds the stage-1 role
    $stage = defaultStage1();
    $stage->update(['max_days' => 3]);

    // Overdue: submitted 10 days ago, no decision → measured from submission.
    $overdue = slaAidAtStage1($stage, Carbon::parse('2026-02-10 12:00:00'));

    // NOT overdue: submitted 10 days ago too, but a decision 1 day ago put it
    // at this stage → measured from the decision, well within the 3-day SLA.
    $fresh = slaAidAtStage1($stage, Carbon::parse('2026-02-10 12:00:00'));
    ApprovalDecision::create([
        'aid_id' => $fresh->id,
        'approval_flow_stage_id' => $stage->id,
        'stage_name' => $stage->name,
        'user_id' => $researcher->id,
        'action' => ApprovalAction::Approve,
        'decided_at' => Carbon::parse('2026-02-19 12:00:00'),
    ]);

    $this->artisan('aids:escalate-overdue')->assertSuccessful();

    expect($overdue->fresh()->approval_escalated_at)->not->toBeNull()
        ->and($fresh->fresh()->approval_escalated_at)->toBeNull()
        ->and($researcher->notifications()->count())->toBe(1);
});

it('escalates once per stage entry and re-arms after a stage change', function () {
    Carbon::setTestNow(Carbon::parse('2026-03-20 12:00:00'));

    $researcher = asResearcher();
    $stage = defaultStage1();
    $stage->update(['max_days' => 3]);

    $aid = slaAidAtStage1($stage, Carbon::parse('2026-03-01 12:00:00'));

    // First sweep: overdue → escalates once.
    $this->artisan('aids:escalate-overdue')->assertSuccessful();

    $firstMarker = $aid->fresh()->approval_escalated_at;
    expect($firstMarker)->not->toBeNull()
        ->and($researcher->notifications()->count())->toBe(1);

    // Second sweep same stage entry: idempotent, no new notification.
    $this->artisan('aids:escalate-overdue')->assertSuccessful();

    expect($aid->fresh()->approval_escalated_at)->toEqual($firstMarker)
        ->and($researcher->notifications()->count())->toBe(1);

    // A decision advances the aid into a new stage entry (after the last
    // escalation), and it goes overdue there too → escalation re-arms.
    ApprovalDecision::create([
        'aid_id' => $aid->id,
        'approval_flow_stage_id' => $stage->id,
        'stage_name' => $stage->name,
        'user_id' => $researcher->id,
        'action' => ApprovalAction::Approve,
        'decided_at' => Carbon::parse('2026-03-25 12:00:00'),
    ]);

    Carbon::setTestNow(Carbon::parse('2026-03-30 12:00:00'));

    $this->artisan('aids:escalate-overdue')->assertSuccessful();

    expect($researcher->notifications()->count())->toBe(2)
        ->and($aid->fresh()->approval_escalated_at)->not->toEqual($firstMarker);
});

it('never escalates a stage with no SLA (null max_days)', function () {
    Carbon::setTestNow(Carbon::parse('2026-04-20 12:00:00'));

    $researcher = asResearcher();
    $stage = defaultStage1();
    $stage->update(['max_days' => null]);

    $aid = slaAidAtStage1($stage, Carbon::parse('2026-01-01 12:00:00')); // ~110 days old

    $this->artisan('aids:escalate-overdue')->assertSuccessful();

    expect($aid->fresh()->approval_escalated_at)->toBeNull()
        ->and($researcher->notifications()->count())->toBe(0);
});

it('flags an overdue aid with a badge in the inbox', function () {
    Carbon::setTestNow(Carbon::parse('2026-05-20 12:00:00'));

    asResearcher();
    $stage = defaultStage1();
    $stage->update(['max_days' => 3]);

    slaAidAtStage1($stage, Carbon::parse('2026-05-05 12:00:00')); // 15 days ago

    Livewire::test(Inbox::class)
        ->assertSee(__('approvals.overdue_badge', ['days' => 15]));
});

<?php

use App\Enums\AidProgramType;
use App\Enums\AidStatus;
use App\Enums\AidType;
use App\Events\Approvals\AidEnteredStage;
use App\Livewire\Aids\ApprovalDecisionModal;
use App\Livewire\Settings\ApprovalFlows\Form;
use App\Models\Aid;
use App\Models\AidProgram;
use App\Models\ApprovalFlow;
use App\Models\Beneficiary;
use App\Models\User;
use App\Notifications\AidAwaitingReviewNotification;
use Database\Factories\AidFactory;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

/**
 * Builds an under_review cash aid pinned at stage 1 of the default flow.
 */
function underReviewAidStage1(): Aid
{
    seedAidCatalog();

    $flow = ApprovalFlow::query()->default()->where('is_active', true)->firstOrFail();
    $stage = $flow->stages()->where('order', 1)->firstOrFail();
    $program = AidProgram::query()->where('type', AidProgramType::Cash)->firstOrFail();
    $beneficiary = Beneficiary::factory()->create();

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

it('lets a specifically-assigned user act on a stage even without its role', function () {
    // A manager holds approvals.act but not the researcher role of stage 1;
    // normally forbidden — until they are assigned to the stage by name.
    $manager = asManager();
    $aid = underReviewAidStage1();

    $stage = $aid->currentStage;
    $stage->update(['assignee_user_ids' => [$manager->id]]);

    Livewire::test(ApprovalDecisionModal::class, ['aid' => $aid->fresh(), 'action' => 'approve'])
        ->call('confirm');

    expect($aid->fresh()->status)->toBe(AidStatus::UnderReview)
        ->and($aid->fresh()->current_stage_id)->not->toBe($stage->id);
});

it('notifies both role holders and specifically-assigned users when a stage is entered', function () {
    Notification::fake();

    $researcher = asResearcher();          // holds the stage-1 role
    $manager = asManager();                // assigned by name, different role
    $aid = underReviewAidStage1();

    $stage = $aid->currentStage;
    $stage->update(['assignee_user_ids' => [$manager->id]]);

    event(new AidEnteredStage($aid->fresh(), $stage->fresh()));

    Notification::assertSentTo($researcher, AidAwaitingReviewNotification::class);
    Notification::assertSentTo($manager, AidAwaitingReviewNotification::class);
});

it('saves an approval stage assigned to specific users with no role', function () {
    $admin = asAdmin();
    $approver = User::factory()->create();

    Livewire::test(Form::class)
        ->set('name', 'مسار الأشخاص')
        ->set('stages', [[
            'name' => 'مراجعة فردية',
            'order' => 1,
            'role' => '',
            'assignee_user_ids' => [$approver->id],
            'allowed_actions' => ['approve', 'reject'],
        ]])
        ->call('save')
        ->assertHasNoErrors();

    $flow = ApprovalFlow::query()->where('name', 'مسار الأشخاص')->firstOrFail();
    $stage = $flow->stages()->firstOrFail();

    expect($stage->role)->toBeNull()
        ->and($stage->assigneeUserIds())->toBe([$approver->id]);
});

it('rejects a stage with neither a role nor any assigned user', function () {
    asAdmin();

    Livewire::test(Form::class)
        ->set('name', 'مسار ناقص')
        ->set('stages', [[
            'name' => 'مرحلة',
            'order' => 1,
            'role' => '',
            'assignee_user_ids' => [],
            'allowed_actions' => ['approve'],
        ]])
        ->call('save')
        ->assertHasErrors('stages.0.role');

    expect(ApprovalFlow::query()->where('name', 'مسار ناقص')->exists())->toBeFalse();
});

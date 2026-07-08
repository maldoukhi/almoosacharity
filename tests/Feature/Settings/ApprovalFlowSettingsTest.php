<?php

use App\Actions\Settings\SaveApprovalFlow;
use App\Enums\AidProgramType;
use App\Enums\AidStatus;
use App\Enums\AidType;
use App\Enums\ApprovalAction;
use App\Enums\RoleName;
use App\Livewire\Settings\ApprovalFlows\Index;
use App\Models\AidProgram;
use App\Models\ApprovalFlow;
use App\Models\Beneficiary;
use Database\Factories\AidFactory;
use Livewire\Livewire;

function approvalFlowStagePayload(): array
{
    return [[
        'name' => 'مرحلة تجريبية',
        'role' => RoleName::SocialResearcher->value,
        'allowed_actions' => [ApprovalAction::Approve->value, ApprovalAction::Reject->value, ApprovalAction::Return->value],
    ]];
}

it('only ever leaves one approval flow marked as default', function () {
    // approvals.configure is reserved to system-admin alone (Gate::before
    // bypass) — none of the seeded roles hold it.
    asAdmin();
    seedAidCatalog();

    $flowOne = app(SaveApprovalFlow::class)->handle([
        'name' => 'المسار الأول',
        'is_default' => true,
        'is_active' => true,
        'stages' => approvalFlowStagePayload(),
    ]);

    expect($flowOne->fresh()->is_default)->toBeTrue();

    $flowTwo = app(SaveApprovalFlow::class)->handle([
        'name' => 'المسار الثاني',
        'is_default' => true,
        'is_active' => true,
        'stages' => approvalFlowStagePayload(),
    ]);

    expect($flowTwo->fresh()->is_default)->toBeTrue();
    expect($flowOne->fresh()->is_default)->toBeFalse();

    // The original seeded default flow must also have lost the flag.
    $seededDefault = ApprovalFlow::query()->where('name', 'المسار الافتراضي')->first();
    expect($seededDefault?->is_default)->toBeFalse();

    expect(ApprovalFlow::query()->where('is_default', true)->count())->toBe(1);
});

it('refuses to delete the default approval flow', function () {
    asAdmin();
    seedAidCatalog();

    $defaultFlow = ApprovalFlow::query()->default()->firstOrFail();

    Livewire::test(Index::class)
        ->call('delete', $defaultFlow->id)
        ->assertDispatched('toast', type: 'error');

    expect(ApprovalFlow::query()->find($defaultFlow->id))->not->toBeNull();
});

it('refuses to rewrite the stages of a flow that currently has an aid under review', function () {
    asAdmin();
    seedAidCatalog();

    $flow = ApprovalFlow::query()->default()->firstOrFail();
    $stage = $flow->stages()->orderBy('order')->firstOrFail();
    $program = AidProgram::query()->where('type', AidProgramType::Cash)->firstOrFail();

    AidFactory::new()->create([
        'beneficiary_id' => Beneficiary::factory()->create()->id,
        'aid_program_id' => $program->id,
        'type' => AidType::Cash,
        'status' => AidStatus::UnderReview,
        'approval_flow_id' => $flow->id,
        'current_stage_id' => $stage->id,
        'amount' => 500,
        'submitted_at' => now(),
    ]);

    expect(fn () => app(SaveApprovalFlow::class)->handle([
        'name' => $flow->name,
        'is_default' => true,
        'is_active' => true,
        'stages' => approvalFlowStagePayload(),
    ], $flow))->toThrow(InvalidArgumentException::class);

    // The original stages must be untouched.
    expect($flow->stages()->count())->toBe(2);
});

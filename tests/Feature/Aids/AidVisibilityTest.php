<?php

use App\Enums\AidProgramType;
use App\Enums\AidStatus;
use App\Enums\AidType;
use App\Enums\RoleName;
use App\Livewire\Aids\Index;
use App\Livewire\Approvals\Inbox;
use App\Models\AidProgram;
use App\Models\ApprovalFlow;
use App\Models\Beneficiary;
use Database\Factories\AidFactory;
use Livewire\Livewire;

it('only shows a user without aids.view-any the aids they created themselves', function () {
    seedAidCatalog();
    $mine = asDataEntry();
    $someoneElse = userWithRole(RoleName::DataEntry);

    $program = AidProgram::query()->where('type', AidProgramType::Cash)->firstOrFail();

    $myAid = AidFactory::new()->draft()->create([
        'reference' => 'AID-MINE-000001',
        'beneficiary_id' => Beneficiary::factory()->create()->id,
        'aid_program_id' => $program->id,
        'type' => AidType::Cash,
        'amount' => 500,
        'created_by' => $mine->id,
    ]);

    $otherAid = AidFactory::new()->draft()->create([
        'reference' => 'AID-OTHER-000001',
        'beneficiary_id' => Beneficiary::factory()->create()->id,
        'aid_program_id' => $program->id,
        'type' => AidType::Cash,
        'amount' => 700,
        'created_by' => $someoneElse->id,
    ]);

    $references = Livewire::test(Index::class)
        ->instance()
        ->aids
        ->pluck('reference');

    expect($references)->toContain($myAid->reference);
    expect($references)->not->toContain($otherAid->reference);
});

it('lets a user with aids.view-any see every aid, regardless of who created it', function () {
    seedAidCatalog();
    asManager();
    $creator = userWithRole(RoleName::DataEntry);

    $program = AidProgram::query()->where('type', AidProgramType::Cash)->firstOrFail();

    $aid = AidFactory::new()->draft()->create([
        'reference' => 'AID-VISIBLE-000001',
        'beneficiary_id' => Beneficiary::factory()->create()->id,
        'aid_program_id' => $program->id,
        'type' => AidType::Cash,
        'amount' => 900,
        'created_by' => $creator->id,
    ]);

    $references = Livewire::test(Index::class)
        ->instance()
        ->aids
        ->pluck('reference');

    expect($references)->toContain($aid->reference);
});

it('shows the approvals inbox only aids waiting at a stage the researcher holds', function () {
    seedAidCatalog();
    $researcher = asResearcher();

    $flow = ApprovalFlow::query()->default()->where('is_active', true)->firstOrFail();
    $researcherStage = $flow->stages()->where('order', 1)->firstOrFail();
    $managerStage = $flow->stages()->where('order', 2)->firstOrFail();
    $program = AidProgram::query()->where('type', AidProgramType::Cash)->firstOrFail();

    $atResearcherStage = AidFactory::new()->create([
        'reference' => 'AID-INBOX-R-000001',
        'beneficiary_id' => Beneficiary::factory()->create()->id,
        'aid_program_id' => $program->id,
        'type' => AidType::Cash,
        'status' => AidStatus::UnderReview,
        'approval_flow_id' => $flow->id,
        'current_stage_id' => $researcherStage->id,
        'amount' => 1200,
        'submitted_at' => now(),
    ]);

    $atManagerStage = AidFactory::new()->create([
        'reference' => 'AID-INBOX-M-000001',
        'beneficiary_id' => Beneficiary::factory()->create()->id,
        'aid_program_id' => $program->id,
        'type' => AidType::Cash,
        'status' => AidStatus::UnderReview,
        'approval_flow_id' => $flow->id,
        'current_stage_id' => $managerStage->id,
        'amount' => 1300,
        'submitted_at' => now(),
    ]);

    $references = Livewire::test(Inbox::class)
        ->instance()
        ->aids
        ->pluck('reference');

    expect($references)->toContain($atResearcherStage->reference);
    expect($references)->not->toContain($atManagerStage->reference);
});

it('shows the approvals inbox only aids waiting at a stage the manager holds', function () {
    seedAidCatalog();
    asManager();

    $flow = ApprovalFlow::query()->default()->where('is_active', true)->firstOrFail();
    $researcherStage = $flow->stages()->where('order', 1)->firstOrFail();
    $managerStage = $flow->stages()->where('order', 2)->firstOrFail();
    $program = AidProgram::query()->where('type', AidProgramType::Cash)->firstOrFail();

    $atResearcherStage = AidFactory::new()->create([
        'reference' => 'AID-INBOX2-R-000001',
        'beneficiary_id' => Beneficiary::factory()->create()->id,
        'aid_program_id' => $program->id,
        'type' => AidType::Cash,
        'status' => AidStatus::UnderReview,
        'approval_flow_id' => $flow->id,
        'current_stage_id' => $researcherStage->id,
        'amount' => 1200,
        'submitted_at' => now(),
    ]);

    $atManagerStage = AidFactory::new()->create([
        'reference' => 'AID-INBOX2-M-000001',
        'beneficiary_id' => Beneficiary::factory()->create()->id,
        'aid_program_id' => $program->id,
        'type' => AidType::Cash,
        'status' => AidStatus::UnderReview,
        'approval_flow_id' => $flow->id,
        'current_stage_id' => $managerStage->id,
        'amount' => 1300,
        'submitted_at' => now(),
    ]);

    $references = Livewire::test(Inbox::class)
        ->instance()
        ->aids
        ->pluck('reference');

    expect($references)->toContain($atManagerStage->reference);
    expect($references)->not->toContain($atResearcherStage->reference);
});

<?php

use Database\Factories\AidFactory;
use App\Actions\Aids\SubmitAid;
use App\Enums\AidProgramType;
use App\Enums\AidStatus;
use App\Enums\AidType;
use App\Exceptions\InvalidAidTransitionException;
use App\Models\Aid;
use App\Models\AidProgram;
use App\Models\ApprovalFlow;
use App\Models\Beneficiary;
use Illuminate\Auth\Access\AuthorizationException;

it('moves a cash draft aid with an amount to under_review at the first stage, with the flow snapshotted', function () {
    seedAidCatalog();
    $creator = asDataEntry();

    $program = AidProgram::query()->where('type', AidProgramType::Cash)->firstOrFail();
    $beneficiary = Beneficiary::factory()->create();

    $aid = AidFactory::new()->draft()->create([
        'beneficiary_id' => $beneficiary->id,
        'aid_program_id' => $program->id,
        'type' => AidType::Cash,
        'amount' => 1500,
        'created_by' => $creator->id,
    ]);

    $updated = app(SubmitAid::class)->handle($aid, $creator);

    $defaultFlow = ApprovalFlow::query()->default()->where('is_active', true)->firstOrFail();
    $firstStage = $defaultFlow->stages()->orderBy('order')->firstOrFail();

    expect($updated->status)->toBe(AidStatus::UnderReview);
    expect($updated->approval_flow_id)->toBe($defaultFlow->id);
    expect($updated->current_stage_id)->toBe($firstStage->id);
    expect($updated->submitted_at)->not->toBeNull();
});

it('refuses to submit an in-kind aid that has no items', function () {
    seedAidCatalog();
    $creator = asDataEntry();

    $program = AidProgram::query()->where('type', AidProgramType::InKind)->firstOrFail();
    $beneficiary = Beneficiary::factory()->create();

    $aid = AidFactory::new()->draft()->create([
        'beneficiary_id' => $beneficiary->id,
        'aid_program_id' => $program->id,
        'type' => AidType::InKind,
        'amount' => null,
        'created_by' => $creator->id,
    ]);

    expect(fn () => app(SubmitAid::class)->handle($aid, $creator))
        ->toThrow(InvalidAidTransitionException::class);

    expect($aid->fresh()->status)->toBe(AidStatus::Draft);
});

it('refuses to submit a cash aid that has no positive amount', function () {
    seedAidCatalog();
    $creator = asDataEntry();

    $program = AidProgram::query()->where('type', AidProgramType::Cash)->firstOrFail();
    $beneficiary = Beneficiary::factory()->create();

    $aid = AidFactory::new()->draft()->create([
        'beneficiary_id' => $beneficiary->id,
        'aid_program_id' => $program->id,
        'type' => AidType::Cash,
        'amount' => 0,
        'created_by' => $creator->id,
    ]);

    expect(fn () => app(SubmitAid::class)->handle($aid, $creator))
        ->toThrow(InvalidAidTransitionException::class);
});

it('refuses to let anyone but the aid creator submit it', function () {
    seedAidCatalog();
    $creator = asDataEntry();
    $someoneElse = userWithRole(\App\Enums\RoleName::DataEntry);

    $program = AidProgram::query()->where('type', AidProgramType::Cash)->firstOrFail();
    $beneficiary = Beneficiary::factory()->create();

    $aid = AidFactory::new()->draft()->create([
        'beneficiary_id' => $beneficiary->id,
        'aid_program_id' => $program->id,
        'type' => AidType::Cash,
        'amount' => 1500,
        'created_by' => $creator->id,
    ]);

    expect(fn () => app(SubmitAid::class)->handle($aid, $someoneElse))
        ->toThrow(AuthorizationException::class);

    expect($aid->fresh()->status)->toBe(AidStatus::Draft);
});

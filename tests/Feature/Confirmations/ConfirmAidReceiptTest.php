<?php

use App\Actions\Confirmations\ConfirmAidReceipt;
use App\Actions\Confirmations\CreateAidConfirmation;
use App\Enums\AidProgramType;
use App\Enums\AidStatus;
use App\Enums\AidType;
use App\Enums\RoleName;
use App\Models\AidProgram;
use App\Models\Beneficiary;
use Database\Factories\AidFactory;

function deliveredAidWithConfirmationAndCreator(): array
{
    seedAidCatalog();

    $creator = asDataEntry();

    $program = AidProgram::query()->where('type', AidProgramType::Cash)->firstOrFail();
    $beneficiary = Beneficiary::factory()->create();

    $aid = AidFactory::new()->approved()->create([
        'beneficiary_id' => $beneficiary->id,
        'aid_program_id' => $program->id,
        'type' => AidType::Cash,
        'amount' => 640,
        'status' => AidStatus::Delivered,
        'created_by' => $creator->id,
    ]);

    ['confirmation' => $confirmation] = app(CreateAidConfirmation::class)->handle($aid);

    return [$aid, $confirmation, $creator];
}

it('sets confirmed_at/ip, moves the aid delivered -> confirmed, and alerts staff', function () {
    [$aid, $confirmation, $creator] = deliveredAidWithConfirmationAndCreator();
    $manager = userWithRole(RoleName::Manager);

    app(ConfirmAidReceipt::class)->handle($confirmation, '203.0.113.5', 'Mozilla/5.0 test agent');

    $confirmation->refresh();
    expect($confirmation->confirmed_at)->not->toBeNull();
    expect($confirmation->confirmed_ip)->toBe('203.0.113.5');

    expect($aid->fresh()->status)->toBe(AidStatus::Confirmed);

    expect($manager->notifications()->count())->toBe(1);
    expect($creator->fresh()->notifications()->count())->toBe(1);
});

it('is idempotent: confirming an already-confirmed link a second time changes nothing', function () {
    [, $confirmation] = deliveredAidWithConfirmationAndCreator();

    app(ConfirmAidReceipt::class)->handle($confirmation, '203.0.113.5', 'agent-one');
    $confirmedAt = $confirmation->fresh()->confirmed_at;

    app(ConfirmAidReceipt::class)->handle($confirmation, '198.51.100.9', 'agent-two');

    $confirmation->refresh();
    expect($confirmation->confirmed_at->equalTo($confirmedAt))->toBeTrue();
    expect($confirmation->confirmed_ip)->toBe('203.0.113.5');
});

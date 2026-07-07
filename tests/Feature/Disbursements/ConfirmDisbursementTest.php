<?php

use App\Actions\Disbursements\ConfirmDisbursement;
use App\Enums\AidProgramType;
use App\Enums\AidStatus;
use App\Enums\AidType;
use App\Enums\DisbursementMethod;
use App\Enums\DisbursementStatus;
use App\Models\AidProgram;
use App\Models\Beneficiary;
use App\Models\Disbursement;
use Database\Factories\AidFactory;
use Illuminate\Auth\Access\AuthorizationException;

function deliveredDisbursement()
{
    seedAidCatalog();

    $program = AidProgram::query()->where('type', AidProgramType::Cash)->firstOrFail();
    $beneficiary = Beneficiary::factory()->create();

    $aid = AidFactory::new()->approved()->create([
        'beneficiary_id' => $beneficiary->id,
        'aid_program_id' => $program->id,
        'type' => AidType::Cash,
        'amount' => 700,
        'status' => AidStatus::Delivered,
    ]);

    return Disbursement::factory()->delivered()->create([
        'aid_id' => $aid->id,
        'method' => DisbursementMethod::OfficePickup,
    ]);
}

it('lets a manager confirm a delivered disbursement', function () {
    $manager = asManager();

    $disbursement = deliveredDisbursement();

    $result = app(ConfirmDisbursement::class)->handle($disbursement, $manager);

    expect($result->status)->toBe(DisbursementStatus::Delivered);
    expect($result->confirmed_by)->toBe($manager->id);
    expect($result->confirmed_at)->not->toBeNull();
});

it('forbids a data-entry user (no disbursements.confirm) from confirming a disbursement', function () {
    $dataEntry = asDataEntry();

    $disbursement = deliveredDisbursement();

    expect(fn () => app(ConfirmDisbursement::class)->handle($disbursement, $dataEntry))
        ->toThrow(AuthorizationException::class);

    expect($disbursement->fresh()->confirmed_at)->toBeNull();
});

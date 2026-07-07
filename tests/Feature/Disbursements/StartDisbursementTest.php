<?php

use App\Actions\Disbursements\StartDisbursement;
use App\Enums\AidProgramType;
use App\Enums\AidStatus;
use App\Enums\AidType;
use App\Enums\DisbursementMethod;
use App\Enums\DisbursementStatus;
use App\Exceptions\InvalidAidTransitionException;
use App\Models\AidProgram;
use App\Models\Beneficiary;
use App\Models\Disbursement;
use Database\Factories\AidFactory;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * An Approved cash aid whose beneficiary has (or lacks) a registered IBAN,
 * ready to be handed to StartDisbursement.
 */
function approvedAidWithBeneficiary(array $beneficiaryOverrides = [], array $aidOverrides = [])
{
    seedAidCatalog();

    $program = AidProgram::query()->where('type', AidProgramType::Cash)->firstOrFail();
    $beneficiary = Beneficiary::factory()->create($beneficiaryOverrides);

    return AidFactory::new()->approved()->create(array_merge([
        'beneficiary_id' => $beneficiary->id,
        'aid_program_id' => $program->id,
        'type' => AidType::Cash,
        'amount' => 1200,
    ], $aidOverrides));
}

it('starts a bank-transfer disbursement with a masked iban snapshot and moves the aid to in_disbursement', function () {
    $actor = asDataEntry();

    $aid = approvedAidWithBeneficiary([
        'iban' => validSaudiIban(),
        'bank_name' => 'بنك الرياض',
        'bank_account_holder' => 'محمد العتيبي',
    ]);

    $disbursement = app(StartDisbursement::class)->handle($aid, DisbursementMethod::BankTransfer, $actor);

    expect($disbursement->status)->toBe(DisbursementStatus::Pending);
    expect($disbursement->bank_account_masked)->toEndWith(substr(validSaudiIban(), -4));
    expect($disbursement->bank_account_masked)->not->toContain(substr(validSaudiIban(), 2, 18));
    expect($disbursement->bank_account_holder_snapshot)->toBe('محمد العتيبي');

    // The masked snapshot never contains the raw IBAN anywhere in it.
    expect($disbursement->bank_account_masked)->not->toBe(validSaudiIban());

    expect($aid->fresh()->status)->toBe(AidStatus::InDisbursement);
});

it('throws when starting a bank-transfer disbursement for a beneficiary with no iban on file', function () {
    $actor = asDataEntry();

    $aid = approvedAidWithBeneficiary(['iban' => null, 'bank_name' => null, 'bank_account_holder' => null]);

    expect(fn () => app(StartDisbursement::class)->handle($aid, DisbursementMethod::BankTransfer, $actor))
        ->toThrow(InvalidAidTransitionException::class);

    expect($aid->fresh()->status)->toBe(AidStatus::Approved);
    expect(Disbursement::query()->where('aid_id', $aid->id)->exists())->toBeFalse();
});

it('throws when starting a disbursement for an aid that is not approved', function () {
    $actor = asDataEntry();

    $aid = approvedAidWithBeneficiary(
        ['iban' => validSaudiIban(), 'bank_account_holder' => 'فهد القحطاني'],
        ['status' => AidStatus::UnderReview, 'decided_at' => null],
    );

    expect(fn () => app(StartDisbursement::class)->handle($aid, DisbursementMethod::OfficePickup, $actor))
        ->toThrow(InvalidAidTransitionException::class);
});

it('forbids a user without disbursements.manage from starting a disbursement', function () {
    $actor = asManager(); // has disbursements.view/confirm, but not .manage

    $aid = approvedAidWithBeneficiary(['iban' => validSaudiIban(), 'bank_account_holder' => 'سالم الدوسري']);

    expect(fn () => app(StartDisbursement::class)->handle($aid, DisbursementMethod::OfficePickup, $actor))
        ->toThrow(AuthorizationException::class);

    expect($aid->fresh()->status)->toBe(AidStatus::Approved);
});

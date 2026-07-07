<?php

use App\Enums\AidProgramType;
use App\Enums\AidStatus;
use App\Enums\AidType;
use App\Enums\DisbursementMethod;
use App\Enums\DisbursementStatus;
use App\Enums\UserStatus;
use App\Models\AidProgram;
use App\Models\Beneficiary;
use App\Models\Disbursement;
use App\Models\User;
use Database\Factories\AidFactory;

function deliveredDisbursementWithProof()
{
    seedAidCatalog();

    $program = AidProgram::query()->where('type', AidProgramType::Cash)->firstOrFail();
    $beneficiary = Beneficiary::factory()->create();

    $aid = AidFactory::new()->approved()->create([
        'beneficiary_id' => $beneficiary->id,
        'aid_program_id' => $program->id,
        'type' => AidType::Cash,
        'amount' => 500,
        'status' => AidStatus::Delivered,
    ]);

    return Disbursement::factory()->delivered()->create([
        'aid_id' => $aid->id,
        'method' => DisbursementMethod::OfficePickup,
        'status' => DisbursementStatus::Delivered,
    ]);
}

it('forbids downloading proof-of-delivery for a user without disbursements.view', function () {
    // No role at all is assigned, so the account holds no permissions and
    // is not the system-admin Gate::before bypass either.
    seedRolesAndPermissions();
    $user = User::factory()->create(['status' => UserStatus::Active]);
    test()->actingAs($user);

    $disbursement = deliveredDisbursementWithProof();

    $this->get(route('disbursements.proof.download', $disbursement))->assertForbidden();
});

it('returns 404 for a user who can view disbursements but the disbursement has no proof file attached', function () {
    asManager();

    $disbursement = deliveredDisbursementWithProof();

    $this->get(route('disbursements.proof.download', $disbursement))->assertNotFound();
});

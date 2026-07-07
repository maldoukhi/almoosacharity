<?php

use App\Actions\Beneficiaries\CreateBeneficiary;
use App\Models\Beneficiary;
use Illuminate\Support\Facades\DB;

it('never stores the plaintext iban in the database column', function () {
    $actor = asDataEntry();

    $plainIban = validSaudiIban();

    $beneficiary = app(CreateBeneficiary::class)->handle(
        beneficiaryAttributes(['iban' => $plainIban, 'bank_name' => 'بنك الرياض', 'bank_account_holder' => 'محمد العتيبي']),
        [],
        $actor,
    );

    $rawIban = DB::table('beneficiaries')->where('id', $beneficiary->id)->value('iban');
    $rawHolder = DB::table('beneficiaries')->where('id', $beneficiary->id)->value('bank_account_holder');

    expect($rawIban)->not->toBeNull();
    expect($rawIban)->not->toBe($plainIban);
    expect($rawIban)->not->toContain($plainIban);
    expect($rawHolder)->not->toBe('محمد العتيبي');

    // The encrypted cast transparently decrypts it back for the app layer.
    expect($beneficiary->fresh()->iban)->toBe($plainIban);
});

it('masks the iban down to only the last 4 digits', function () {
    $actor = asDataEntry();

    $plainIban = validSaudiIban();

    $beneficiary = app(CreateBeneficiary::class)->handle(
        beneficiaryAttributes(['iban' => $plainIban, 'bank_name' => 'بنك الرياض', 'bank_account_holder' => 'محمد العتيبي']),
        [],
        $actor,
    );

    $masked = $beneficiary->maskedIban();

    expect($masked)->toEndWith(substr($plainIban, -4));
    expect($masked)->not->toContain(substr($plainIban, 2, 18));
});

it('returns null from maskedIban when there is no iban on file', function () {
    // BeneficiaryFactory::definition() resolves `created_by` from an
    // existing data-entry/system-admin user, so one must exist first for
    // the created_by foreign key to resolve.
    asDataEntry();

    $beneficiary = Beneficiary::factory()->create(['iban' => null, 'bank_name' => null, 'bank_account_holder' => null]);

    expect($beneficiary->maskedIban())->toBeNull();
});

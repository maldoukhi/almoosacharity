<?php

use App\Actions\Beneficiaries\CreateBeneficiary;
use App\Models\Beneficiary;

it('stores blank optional fields as null, not empty strings', function () {
    $actor = asDataEntry();

    $beneficiary = app(CreateBeneficiary::class)->handle(
        beneficiaryAttributes([
            'national_id' => '1'.random_int(100000000, 999999999),
            'birth_date' => '',
            'family_members_count' => '',
            'monthly_income' => '',
            'rent_amount' => '',
        ]),
        [],
        $actor,
    );

    $row = Beneficiary::query()->whereKey($beneficiary->id)->first();

    // Nullable date/numeric columns become null (MySQL strict mode rejects '').
    expect($row->getRawOriginal('birth_date'))->toBeNull()
        ->and($row->getRawOriginal('monthly_income'))->toBeNull()
        ->and($row->getRawOriginal('rent_amount'))->toBeNull()
        ->and($row->getRawOriginal('family_members_count'))->toBeNull();
});

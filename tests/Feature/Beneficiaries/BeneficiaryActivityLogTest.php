<?php

use App\Actions\Beneficiaries\CreateBeneficiary;
use App\Actions\Beneficiaries\UpdateBeneficiary;
use Spatie\Activitylog\Models\Activity;

it('never records iban or bank_account_holder in the activity log attribute changes when updating a beneficiary', function () {
    $actor = asDataEntry();

    $beneficiary = app(CreateBeneficiary::class)->handle(
        beneficiaryAttributes([
            'iban' => validSaudiIban(),
            'bank_name' => 'بنك الرياض',
            'bank_account_holder' => 'محمد العتيبي',
        ]),
        [],
        $actor,
    );

    // Change both a plain field and every bank field in the same update.
    app(UpdateBeneficiary::class)->handle(
        $beneficiary,
        [
            'city' => 'جدة',
            'iban' => 'SA1234567890123456789012',
            'bank_account_holder' => 'أحمد الحربي',
        ],
        [],
        $actor,
    );

    $activity = Activity::query()
        ->where('subject_type', $beneficiary->getMorphClass())
        ->where('subject_id', $beneficiary->id)
        ->latest('id')
        ->first();

    expect($activity)->not->toBeNull();

    // attribute_changes is shaped as ['attributes' => [...new values],
    // 'old' => [...previous values]], not a flat field => value map.
    $changes = $activity->attribute_changes;
    $newValues = $changes['attributes'] ?? [];
    $oldValues = $changes['old'] ?? [];

    expect($newValues)->toHaveKey('city');
    expect($newValues)->not->toHaveKey('iban');
    expect($newValues)->not->toHaveKey('bank_account_holder');
    expect($oldValues)->not->toHaveKey('iban');
    expect($oldValues)->not->toHaveKey('bank_account_holder');

    // bank_name is not treated as sensitive (only the account number and
    // holder name are), so it may legitimately appear here — no assertion
    // against it either way.

    // Belt and braces: the raw serialized column must not leak the
    // plaintext bank values either.
    expect(json_encode($changes))->not->toContain('SA1234567890123456789012');
    expect(json_encode($changes))->not->toContain('أحمد الحربي');
});

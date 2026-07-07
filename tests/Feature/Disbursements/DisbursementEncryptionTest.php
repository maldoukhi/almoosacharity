<?php

use App\Actions\Disbursements\StartDisbursement;
use App\Enums\AidProgramType;
use App\Enums\AidType;
use App\Enums\DisbursementMethod;
use App\Models\AidProgram;
use App\Models\Beneficiary;
use Database\Factories\AidFactory;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;

it('encrypts bank_account_holder_snapshot at rest and never leaks it into the activity log', function () {
    $actor = asDataEntry();

    seedAidCatalog();
    $program = AidProgram::query()->where('type', AidProgramType::Cash)->firstOrFail();
    $beneficiary = Beneficiary::factory()->create([
        'iban' => validSaudiIban(),
        'bank_name' => 'بنك الرياض',
        'bank_account_holder' => 'عبدالعزيز الشمري',
    ]);

    $aid = AidFactory::new()->approved()->create([
        'beneficiary_id' => $beneficiary->id,
        'aid_program_id' => $program->id,
        'type' => AidType::Cash,
        'amount' => 1000,
    ]);

    $disbursement = app(StartDisbursement::class)->handle($aid, DisbursementMethod::BankTransfer, $actor);

    // 1. Raw column value is not the plaintext holder name.
    $rawSnapshot = DB::table('disbursements')->where('id', $disbursement->id)->value('bank_account_holder_snapshot');

    expect($rawSnapshot)->not->toBeNull();
    expect($rawSnapshot)->not->toBe('عبدالعزيز الشمري');
    expect($rawSnapshot)->not->toContain('عبدالعزيز الشمري');

    // The encrypted cast transparently decrypts it back for the app layer.
    expect($disbursement->fresh()->bank_account_holder_snapshot)->toBe('عبدالعزيز الشمري');

    // 2. The activity log entry for the disbursement's creation never
    // records the snapshot, in either its new or old attribute_changes.
    $activity = Activity::query()
        ->where('subject_type', $disbursement->getMorphClass())
        ->where('subject_id', $disbursement->id)
        ->latest('id')
        ->first();

    expect($activity)->not->toBeNull();

    $changes = $activity->attribute_changes;
    $newValues = $changes['attributes'] ?? [];
    $oldValues = $changes['old'] ?? [];

    expect($newValues)->not->toHaveKey('bank_account_holder_snapshot');
    expect($oldValues)->not->toHaveKey('bank_account_holder_snapshot');
    expect(json_encode($changes))->not->toContain('عبدالعزيز الشمري');
});

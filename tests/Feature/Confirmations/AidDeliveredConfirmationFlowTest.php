<?php

use App\Actions\Disbursements\RecordDelivery;
use App\Enums\AidProgramType;
use App\Enums\AidStatus;
use App\Enums\AidType;
use App\Enums\DisbursementMethod;
use App\Enums\DisbursementStatus;
use App\Models\AidConfirmation;
use App\Models\AidProgram;
use App\Models\Beneficiary;
use App\Models\Disbursement;
use App\Models\MessageLog;
use Database\Factories\AidFactory;

/**
 * Phase 5/6b wiring: as soon as RecordDelivery moves an aid to Delivered
 * and fires AidDelivered, two queued listeners run (QUEUE_CONNECTION=sync
 * in tests, so this happens inline, with no need for Queue::fake):
 * CreateConfirmationOnDelivery (creates the aid_confirmations row + sends
 * the link) and SendBeneficiaryAidNotification (the beneficiary-facing
 * "your aid was delivered" SMS). Both go through Messenger, which always
 * writes a message_logs row up front.
 */
it('creates an aid_confirmation and logs the outbound sms(s) when an aid is delivered', function () {
    (new \Database\Seeders\NotificationTemplateSeeder)->run();

    $actor = asDataEntry();

    seedAidCatalog();
    $program = AidProgram::query()->where('type', AidProgramType::Cash)->firstOrFail();
    $beneficiary = Beneficiary::factory()->create(['mobile' => '0501112222']);

    $aid = AidFactory::new()->approved()->create([
        'beneficiary_id' => $beneficiary->id,
        'aid_program_id' => $program->id,
        'type' => AidType::Cash,
        'amount' => 800,
        'status' => AidStatus::InDisbursement,
    ]);

    $disbursement = Disbursement::factory()->create([
        'aid_id' => $aid->id,
        'method' => DisbursementMethod::OfficePickup,
        'status' => DisbursementStatus::Pending,
    ]);

    expect(AidConfirmation::query()->where('aid_id', $aid->id)->exists())->toBeFalse();

    app(RecordDelivery::class)->handle($disbursement, $actor, ['receipt_number' => 'RCPT-9']);

    $confirmation = AidConfirmation::query()->where('aid_id', $aid->id)->first();

    expect($confirmation)->not->toBeNull();
    expect($confirmation->sent_at)->not->toBeNull();
    expect($confirmation->token_hash)->not->toBeNull();

    // One message_log for the "aid delivered" beneficiary notification,
    // and one for the confirmation link itself — both to the same mobile.
    $logs = MessageLog::query()->where('recipient', '0501112222')->get();

    expect($logs)->toHaveCount(2);
    expect($logs->contains(fn (MessageLog $log): bool => str_contains($log->body, 'http')))->toBeTrue();
});

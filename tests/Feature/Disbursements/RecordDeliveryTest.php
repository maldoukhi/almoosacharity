<?php

use App\Actions\Disbursements\RecordDelivery;
use App\Enums\AidProgramType;
use App\Enums\AidStatus;
use App\Enums\AidType;
use App\Enums\DisbursementMethod;
use App\Enums\DisbursementStatus;
use App\Events\Aids\AidDelivered;
use App\Exceptions\InvalidAidTransitionException;
use App\Models\AidProgram;
use App\Models\Beneficiary;
use App\Models\Disbursement;
use Database\Factories\AidFactory;
use Illuminate\Support\Facades\Event;

/**
 * A cash aid currently in_disbursement with a pending office-pickup
 * disbursement row, ready for RecordDelivery.
 */
function inDisbursementAidWithPendingRow(array $disbursementOverrides = [])
{
    seedAidCatalog();

    $program = AidProgram::query()->where('type', AidProgramType::Cash)->firstOrFail();
    $beneficiary = Beneficiary::factory()->create();

    $aid = AidFactory::new()->approved()->create([
        'beneficiary_id' => $beneficiary->id,
        'aid_program_id' => $program->id,
        'type' => AidType::Cash,
        'amount' => 900,
        'status' => AidStatus::InDisbursement,
    ]);

    $disbursement = Disbursement::factory()->create(array_merge([
        'aid_id' => $aid->id,
        'method' => DisbursementMethod::OfficePickup,
        'status' => DisbursementStatus::Pending,
    ], $disbursementOverrides));

    return [$aid, $disbursement];
}

it('completes delivery, fills delivered_by/at, and dispatches AidDelivered', function () {
    Event::fake([AidDelivered::class]);

    $actor = asDataEntry();
    [$aid, $disbursement] = inDisbursementAidWithPendingRow();

    $result = app(RecordDelivery::class)->handle($disbursement, $actor, [
        'receipt_number' => 'RCPT-000123',
    ]);

    expect($result->status)->toBe(DisbursementStatus::Delivered);
    expect($result->delivered_by)->toBe($actor->id);
    expect($result->delivered_at)->not->toBeNull();
    expect($result->receipt_number)->toBe('RCPT-000123');

    expect($aid->fresh()->status)->toBe(AidStatus::Delivered);

    Event::assertDispatched(AidDelivered::class, fn (AidDelivered $event): bool => $event->aid->id === $aid->id);
});

it('throws when recording delivery on a disbursement that is already delivered', function () {
    $actor = asDataEntry();
    [$aid, $disbursement] = inDisbursementAidWithPendingRow(['status' => DisbursementStatus::Delivered, 'delivered_at' => now()]);

    // The aid itself also needs to reflect the already-delivered state for
    // the guard inside RecordDelivery to trip (it checks both).
    $aid->update(['status' => AidStatus::Delivered]);

    expect(fn () => app(RecordDelivery::class)->handle($disbursement, $actor, []))
        ->toThrow(InvalidAidTransitionException::class);
});

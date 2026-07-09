<?php

use App\Actions\Confirmations\ConfirmAidReceipt;
use App\Actions\Confirmations\CreateAidConfirmation;
use App\Enums\AidProgramType;
use App\Enums\AidStatus;
use App\Enums\AidType;
use App\Enums\ReceiptStatus;
use App\Enums\RoleName;
use App\Livewire\Aids\Index as AidsIndex;
use App\Livewire\Public\ConfirmReceipt;
use App\Models\Aid;
use App\Models\AidConfirmation;
use App\Models\AidItem;
use App\Models\AidProgram;
use App\Models\Beneficiary;
use App\Support\Settings;
use Database\Factories\AidFactory;
use Database\Factories\AidItemFactory;
use Illuminate\Support\Collection;
use Livewire\Livewire;

/**
 * A delivered in-kind aid (three items) plus its freshly issued
 * confirmation + raw token, for exercising the partial/not-received
 * receipt outcomes and the per-item checkboxes.
 *
 * @return array{0: Aid, 1: Collection<int, AidItem>, 2: AidConfirmation, 3: string}
 */
function deliveredInKindAidWithConfirmation(): array
{
    seedAidCatalog();
    userWithRole(RoleName::DataEntry);

    $program = AidProgram::query()->where('type', AidProgramType::InKind)->firstOrFail();
    $beneficiary = Beneficiary::factory()->create();

    $aid = AidFactory::new()->approved()->create([
        'beneficiary_id' => $beneficiary->id,
        'aid_program_id' => $program->id,
        'type' => AidType::InKind,
        'amount' => 0,
        'status' => AidStatus::Delivered,
    ]);

    $items = AidItemFactory::new()->count(3)->create(['aid_id' => $aid->id]);

    ['confirmation' => $confirmation, 'rawToken' => $rawToken] = app(CreateAidConfirmation::class)->handle($aid);

    return [$aid, $items, $confirmation, $rawToken];
}

it('records a partial receipt with its note and ticked item ids, still confirming the aid', function () {
    [$aid, $items, $confirmation, $rawToken] = deliveredInKindAidWithConfirmation();

    Livewire::test(ConfirmReceipt::class, ['token' => $rawToken])
        ->set('receiptStatus', ReceiptStatus::Partial->value)
        ->set('receivedItemIds', [$items[0]->id, $items[1]->id])
        ->set('receiptNote', 'استلمت صنفين فقط')
        ->call('confirm')
        ->assertSet('view', 'success');

    $confirmation->refresh();
    expect($confirmation->receipt_status)->toBe(ReceiptStatus::Partial);
    expect($confirmation->receipt_note)->toBe('استلمت صنفين فقط');
    expect($confirmation->received_item_ids)->toEqualCanonicalizing([$items[0]->id, $items[1]->id]);

    // A documented partial receipt is still a confirmation.
    expect($aid->fresh()->status)->toBe(AidStatus::Confirmed);
    expect($confirmation->confirmed_at)->not->toBeNull();
});

it('drops ticked item ids that do not belong to this aid', function () {
    [, $items, $confirmation, $rawToken] = deliveredInKindAidWithConfirmation();

    Livewire::test(ConfirmReceipt::class, ['token' => $rawToken])
        ->set('receiptStatus', ReceiptStatus::Partial->value)
        ->set('receivedItemIds', [$items[0]->id, 999999])
        ->call('confirm')
        ->assertSet('view', 'success');

    expect($confirmation->fresh()->received_item_ids)->toEqualCanonicalizing([$items[0]->id]);
});

it('records a not-received report with its note and no item list', function () {
    [$aid, , $confirmation, $rawToken] = deliveredInKindAidWithConfirmation();

    Livewire::test(ConfirmReceipt::class, ['token' => $rawToken])
        ->set('receiptStatus', ReceiptStatus::NotReceived->value)
        ->set('receiptNote', 'لم يصل المندوب')
        ->call('confirm')
        ->assertSet('view', 'success');

    $confirmation->refresh();
    expect($confirmation->receipt_status)->toBe(ReceiptStatus::NotReceived);
    expect($confirmation->receipt_note)->toBe('لم يصل المندوب');
    expect($confirmation->received_item_ids)->toBeNull();
    expect($aid->fresh()->status)->toBe(AidStatus::Confirmed);
});

it('a full receipt keeps no note or item list even if some were set', function () {
    [, $items, $confirmation, $rawToken] = deliveredInKindAidWithConfirmation();

    Livewire::test(ConfirmReceipt::class, ['token' => $rawToken])
        ->set('receiptStatus', ReceiptStatus::Received->value)
        ->set('receivedItemIds', [$items[0]->id])
        ->set('receiptNote', 'ملاحظة')
        ->call('confirm')
        ->assertSet('view', 'success');

    $confirmation->refresh();
    expect($confirmation->receipt_status)->toBe(ReceiptStatus::Received);
    expect($confirmation->receipt_note)->toBeNull();
    expect($confirmation->received_item_ids)->toBeNull();
});

it('blocks submission when a signature is required but none was drawn', function () {
    app(Settings::class)->set('confirmation_signature_required', '1');

    [, , $confirmation, $rawToken] = deliveredInKindAidWithConfirmation();

    Livewire::test(ConfirmReceipt::class, ['token' => $rawToken])
        ->assertSet('signatureRequired', true)
        ->call('confirm')
        ->assertHasErrors('signature')
        ->assertSet('view', 'confirm');

    expect($confirmation->fresh()->confirmed_at)->toBeNull();
});

it('allows submission when a signature is required and one is present', function () {
    app(Settings::class)->set('confirmation_signature_required', '1');

    [, , $confirmation, $rawToken] = deliveredInKindAidWithConfirmation();

    Livewire::test(ConfirmReceipt::class, ['token' => $rawToken])
        ->set('signature', 'data:image/png;base64,'.base64_encode('fake-png-bytes'))
        ->call('confirm')
        ->assertHasNoErrors()
        ->assertSet('view', 'success');

    expect($confirmation->fresh()->confirmed_at)->not->toBeNull();
});

it('keeps the signature optional when the setting is not enabled', function () {
    [, , $confirmation, $rawToken] = deliveredInKindAidWithConfirmation();

    Livewire::test(ConfirmReceipt::class, ['token' => $rawToken])
        ->assertSet('signatureRequired', false)
        ->call('confirm')
        ->assertHasNoErrors()
        ->assertSet('view', 'success');

    expect($confirmation->fresh()->confirmed_at)->not->toBeNull();
});

it('marks aids with a partial/not-received receipt on the aids list', function () {
    [, , $confirmation] = deliveredInKindAidWithConfirmation();

    app(ConfirmAidReceipt::class)->handle(
        $confirmation, '203.0.113.5', 'agent', '', ReceiptStatus::NotReceived,
    );

    asAdmin();

    Livewire::test(AidsIndex::class)
        ->assertSee(ReceiptStatus::NotReceived->label());
});

it('filters the aids list by receipt status', function () {
    // One aid reported not-received.
    [, , $flagged] = deliveredInKindAidWithConfirmation();
    app(ConfirmAidReceipt::class)->handle($flagged, '203.0.113.5', 'agent', '', ReceiptStatus::NotReceived);
    $flaggedRef = $flagged->aid->reference;

    // A second, fully-received aid that must be filtered out.
    [, , $clean] = deliveredInKindAidWithConfirmation();
    app(ConfirmAidReceipt::class)->handle($clean, '203.0.113.6', 'agent', '', ReceiptStatus::Received);
    $cleanRef = $clean->aid->reference;

    asAdmin();

    Livewire::test(AidsIndex::class)
        ->set('receiptFilter', ReceiptStatus::NotReceived->value)
        ->assertSee($flaggedRef)
        ->assertDontSee($cleanRef);
});

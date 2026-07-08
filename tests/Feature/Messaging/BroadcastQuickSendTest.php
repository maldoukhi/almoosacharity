<?php

use App\Actions\Beneficiaries\CreateBeneficiary;
use App\Livewire\Beneficiaries\Index as BeneficiaryIndex;
use Livewire\Livewire;

function quickSendBeneficiary(array $overrides = [])
{
    $actor = asDataEntry();

    return app(CreateBeneficiary::class)->handle(
        beneficiaryAttributes(array_merge([
            'national_id' => (string) random_int(3000000000, 3999999999),
        ], $overrides)),
        [],
        $actor,
    );
}

it('redirects a manager to the broadcast screen with the checked beneficiary ids', function () {
    $one = quickSendBeneficiary();
    $two = quickSendBeneficiary();

    asManager();

    Livewire::test(BeneficiaryIndex::class)
        ->set('selected', [$one->id, $two->id])
        ->call('sendBroadcast')
        ->assertRedirect(route('admin.messaging.broadcast', ['ids' => "{$one->id},{$two->id}"]));
});

it('does not show the quick-send button data to a user without messages.broadcast', function () {
    quickSendBeneficiary();

    asResearcher();

    Livewire::test(BeneficiaryIndex::class)
        ->set('selected', [1])
        ->assertDontSee(__('messaging.quick_send.button'));
});

it('forbids calling sendBroadcast directly without messages.broadcast', function () {
    $beneficiary = quickSendBeneficiary();

    asResearcher();

    Livewire::test(BeneficiaryIndex::class)
        ->set('selected', [$beneficiary->id])
        ->call('sendBroadcast')
        ->assertForbidden();
});

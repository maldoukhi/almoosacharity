<?php

use App\Actions\Confirmations\CreateAidConfirmation;
use App\Enums\AidProgramType;
use App\Enums\AidStatus;
use App\Enums\AidType;
use App\Enums\RoleName;
use App\Livewire\Public\ConfirmReceipt;
use App\Models\Aid;
use App\Models\AidConfirmation;
use App\Models\AidProgram;
use App\Models\Beneficiary;
use Database\Factories\AidFactory;
use Livewire\Livewire;

/**
 * A delivered cash aid with a real, non-blank beneficiary IBAN/national id
 * (so tests 9's "no sensitive data" assertions have something concrete to
 * check for), plus its freshly issued confirmation + raw token.
 *
 * @return array{0: Aid, 1: AidConfirmation, 2: string}
 */
function deliveredAidWithFreshConfirmation(array $aidOverrides = []): array
{
    seedAidCatalog();

    // BeneficiaryFactory::definition() resolves `created_by` from an
    // existing data-entry/system-admin user (falling back to the
    // hardcoded, non-existent id 1 otherwise), so one must exist first.
    userWithRole(RoleName::DataEntry);

    $program = AidProgram::query()->where('type', AidProgramType::Cash)->firstOrFail();
    $beneficiary = Beneficiary::factory()->create([
        'iban' => validSaudiIban(),
        'bank_account_holder' => 'ريم الغامدي',
        'national_id' => '1234567890',
    ]);

    $aid = AidFactory::new()->approved()->create(array_merge([
        'beneficiary_id' => $beneficiary->id,
        'aid_program_id' => $program->id,
        'type' => AidType::Cash,
        'amount' => 3456.78,
        'status' => AidStatus::Delivered,
    ], $aidOverrides));

    ['confirmation' => $confirmation, 'rawToken' => $rawToken] = app(CreateAidConfirmation::class)->handle($aid);

    return [$aid, $confirmation, $rawToken];
}

it('shows the confirm page for a short token-only link, and records opened_at once', function () {
    [, $confirmation, $rawToken] = deliveredAidWithFreshConfirmation();

    $link = route('public.confirm', ['token' => $rawToken]);

    // The link is short: the raw token in the path, no signature query
    // string and no confirmation id.
    expect($link)->toContain('/c/'.$rawToken);
    expect($link)->not->toContain('signature=');

    expect($confirmation->opened_at)->toBeNull();

    $this->get($link)->assertOk();

    $confirmation->refresh();
    expect($confirmation->opened_at)->not->toBeNull();
    $firstOpenedAt = $confirmation->opened_at;

    // Visiting again does not move opened_at forward a second time.
    $this->get($link)->assertOk();
    expect($confirmation->fresh()->opened_at->equalTo($firstOpenedAt))->toBeTrue();
});

it('shows a friendly not_found page for an unknown/wrong token', function () {
    deliveredAidWithFreshConfirmation();

    $response = $this->get(route('public.confirm', ['token' => 'not-a-real-token']));

    // The token matched no row: friendly not_found copy, never a 500 and
    // never a hint that any given link exists.
    $response->assertOk();
    $response->assertSee(__('confirmations.not_found_title'));
});

it('shows a friendly expired page (not a 500) for a model-expired confirmation', function () {
    [, $confirmation, $rawToken] = deliveredAidWithFreshConfirmation();

    // Expire the confirmation itself: the token still resolves the row,
    // and ConfirmReceipt's own isExpired() check is what shows the expired
    // copy now that there is no signed middleware in front of the route.
    $confirmation->update(['expires_at' => now()->subDay()]);

    $response = $this->get(route('public.confirm', ['token' => $rawToken]));

    $response->assertOk();
    $response->assertSee(__('confirmations.expired_title'));
});

it('never exposes the cash amount, iban, or national id on the public confirmation page', function () {
    [$aid, , $rawToken] = deliveredAidWithFreshConfirmation();

    $response = $this->get(route('public.confirm', ['token' => $rawToken]));

    $response->assertOk();
    $response->assertDontSee(number_format((float) $aid->amount, 2));
    $response->assertDontSee(validSaudiIban());
    $response->assertDontSee('1234567890');
});

it('saves a signature drawn on the confirm step to the media collection', function () {
    [, $confirmation, $rawToken] = deliveredAidWithFreshConfirmation();

    $signature = 'data:image/png;base64,'.base64_encode('fake-png-bytes');

    Livewire::test(ConfirmReceipt::class, ['token' => $rawToken])
        ->set('signature', $signature)
        ->call('confirm')
        ->assertSet('view', 'success');

    expect($confirmation->fresh()->getFirstMedia('confirmation_signature'))->not->toBeNull();
});

it('does not save a signature when none was drawn', function () {
    [, $confirmation, $rawToken] = deliveredAidWithFreshConfirmation();

    Livewire::test(ConfirmReceipt::class, ['token' => $rawToken])
        ->call('confirm')
        ->assertSet('view', 'success');

    expect($confirmation->fresh()->getFirstMedia('confirmation_signature'))->toBeNull();
});

it('rate limits the public confirmation route after 10 requests per minute', function () {
    [, , $rawToken] = deliveredAidWithFreshConfirmation();

    $link = route('public.confirm', ['token' => $rawToken]);

    for ($i = 0; $i < 10; $i++) {
        $this->get($link)->assertOk();
    }

    $this->get($link)->assertStatus(429);
});

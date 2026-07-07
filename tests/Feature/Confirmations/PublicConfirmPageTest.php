<?php

use App\Actions\Confirmations\CreateAidConfirmation;
use App\Enums\AidProgramType;
use App\Enums\AidStatus;
use App\Enums\AidType;
use App\Models\AidProgram;
use App\Models\Beneficiary;
use Database\Factories\AidFactory;
use Illuminate\Support\Facades\URL;

/**
 * A delivered cash aid with a real, non-blank beneficiary IBAN/national id
 * (so tests 9's "no sensitive data" assertions have something concrete to
 * check for), plus its freshly issued confirmation + raw token.
 *
 * @return array{0: \App\Models\Aid, 1: \App\Models\AidConfirmation, 2: string}
 */
function deliveredAidWithFreshConfirmation(array $aidOverrides = []): array
{
    seedAidCatalog();

    // BeneficiaryFactory::definition() resolves `created_by` from an
    // existing data-entry/system-admin user (falling back to the
    // hardcoded, non-existent id 1 otherwise), so one must exist first.
    userWithRole(\App\Enums\RoleName::DataEntry);

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

it('shows the confirm page for a valid signed link and token, and records opened_at once', function () {
    [, $confirmation, $rawToken] = deliveredAidWithFreshConfirmation();

    $link = URL::temporarySignedRoute(
        'public.confirm',
        $confirmation->expires_at,
        ['confirmation' => $confirmation->id, 'token' => $rawToken],
    );

    expect($confirmation->opened_at)->toBeNull();

    $this->get($link)->assertOk();

    $confirmation->refresh();
    expect($confirmation->opened_at)->not->toBeNull();
    $firstOpenedAt = $confirmation->opened_at;

    // Visiting again does not move opened_at forward a second time.
    $this->get($link)->assertOk();
    expect($confirmation->fresh()->opened_at->equalTo($firstOpenedAt))->toBeTrue();
});

it('rejects a validly signed link whose token does not match the confirmation', function () {
    [, $confirmation] = deliveredAidWithFreshConfirmation();

    // Signed correctly for *this* (wrong) token value — the signature
    // itself passes, only the component's own hash_equals() check fails.
    $link = URL::temporarySignedRoute(
        'public.confirm',
        $confirmation->expires_at,
        ['confirmation' => $confirmation->id, 'token' => 'not-the-real-token'],
    );

    $this->get($link)->assertForbidden();
});

it('rejects an unsigned confirmation link', function () {
    [, $confirmation, $rawToken] = deliveredAidWithFreshConfirmation();

    $unsigned = '/confirm/'.$confirmation->id.'?token='.$rawToken;

    $this->get($unsigned)->assertStatus(403);
});

it('shows a friendly expired page (not a 500) for a model-expired confirmation', function () {
    [, $confirmation, $rawToken] = deliveredAidWithFreshConfirmation();

    // Expire the confirmation itself while keeping the *signature's own*
    // expiry further in the future, so the 'signed' middleware lets the
    // request through and ConfirmReceipt's own isExpired() second line of
    // defense is what's actually being exercised here.
    $confirmation->update(['expires_at' => now()->subDay()]);

    $link = URL::temporarySignedRoute(
        'public.confirm',
        now()->addDay(),
        ['confirmation' => $confirmation->id, 'token' => $rawToken],
    );

    $response = $this->get($link);

    $response->assertOk();
    $response->assertSee(__('confirmations.expired_title'));
});

it('never exposes the cash amount, iban, or national id on the public confirmation page', function () {
    [$aid, $confirmation, $rawToken] = deliveredAidWithFreshConfirmation();

    $link = URL::temporarySignedRoute(
        'public.confirm',
        $confirmation->expires_at,
        ['confirmation' => $confirmation->id, 'token' => $rawToken],
    );

    $response = $this->get($link);

    $response->assertOk();
    $response->assertDontSee(number_format((float) $aid->amount, 2));
    $response->assertDontSee(validSaudiIban());
    $response->assertDontSee('1234567890');
});

it('rate limits the public confirmation route after 10 requests per minute', function () {
    [, $confirmation, $rawToken] = deliveredAidWithFreshConfirmation();

    $link = URL::temporarySignedRoute(
        'public.confirm',
        $confirmation->expires_at,
        ['confirmation' => $confirmation->id, 'token' => $rawToken],
    );

    for ($i = 0; $i < 10; $i++) {
        $this->get($link)->assertOk();
    }

    $this->get($link)->assertStatus(429);
});

<?php

use App\Enums\AidProgramType;
use App\Enums\AidStatus;
use App\Enums\AidType;
use App\Enums\ApprovalStageType;
use App\Enums\RoleName;
use App\Livewire\Public\BeneficiaryStageResponse as PublicPage;
use App\Models\Aid;
use App\Models\AidProgram;
use App\Models\ApprovalFlow;
use App\Models\Beneficiary;
use App\Models\BeneficiaryStageResponse;
use Database\Factories\AidFactory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('local');
});

/**
 * Build an under-review cash aid parked at the default flow's LAST stage,
 * made a beneficiary_response stage, plus a freshly issued response record
 * and its raw token.
 *
 * @return array{0: Aid, 1: BeneficiaryStageResponse, 2: string}
 */
function beneficiaryResponseFixture(array $aidOverrides = []): array
{
    seedAidCatalog();
    userWithRole(RoleName::DataEntry);

    $flow = ApprovalFlow::query()->default()->where('is_active', true)->firstOrFail();
    $stage = $flow->stages()->where('order', 2)->firstOrFail();
    $stage->update(['type' => ApprovalStageType::BeneficiaryResponse->value]);

    $program = AidProgram::query()->where('type', AidProgramType::Cash)->firstOrFail();
    $beneficiary = Beneficiary::factory()->create([
        'iban' => validSaudiIban(),
        'national_id' => '1234567890',
    ]);

    $aid = AidFactory::new()->create(array_merge([
        'beneficiary_id' => $beneficiary->id,
        'aid_program_id' => $program->id,
        'type' => AidType::Cash,
        'amount' => 3456.78,
        'status' => AidStatus::UnderReview,
        'approval_flow_id' => $flow->id,
        'current_stage_id' => $stage->id,
        'submitted_at' => now(),
    ], $aidOverrides));

    $rawToken = Str::random(24);

    $response = BeneficiaryStageResponse::query()->create([
        'aid_id' => $aid->id,
        'approval_flow_stage_id' => $stage->id,
        'stage_name' => $stage->name,
        'token_hash' => BeneficiaryStageResponse::hashToken($rawToken),
        'expires_at' => now()->addDays(7),
        'sent_at' => now(),
    ]);

    return [$aid, $response, $rawToken];
}

it('renders the respond page for a valid token and records opened_at once', function () {
    [, $response, $rawToken] = beneficiaryResponseFixture();

    $link = route('public.stage-response', ['token' => $rawToken]);
    expect($link)->toContain('/r/'.$rawToken);

    expect($response->opened_at)->toBeNull();

    $this->get($link)->assertOk()->assertSee(__('beneficiary_stage.intro'));

    expect($response->fresh()->opened_at)->not->toBeNull();
});

it('never exposes the cash amount, iban or national id', function () {
    [$aid, , $rawToken] = beneficiaryResponseFixture();

    $res = $this->get(route('public.stage-response', ['token' => $rawToken]));

    $res->assertOk();
    $res->assertDontSee(number_format((float) $aid->amount, 2));
    $res->assertDontSee(validSaudiIban());
    $res->assertDontSee('1234567890');
});

it('shows not_found for an unknown token', function () {
    beneficiaryResponseFixture();

    $this->get(route('public.stage-response', ['token' => 'not-a-real-token']))
        ->assertOk()
        ->assertSee(__('beneficiary_stage.not_found_title'));
});

it('shows the expired page for a model-expired response', function () {
    [, $response, $rawToken] = beneficiaryResponseFixture();

    $response->update(['expires_at' => now()->subDay()]);

    $this->get(route('public.stage-response', ['token' => $rawToken]))
        ->assertOk()
        ->assertSee(__('beneficiary_stage.expired_title'));
});

it('shows the already-responded page for a used token', function () {
    [, $response, $rawToken] = beneficiaryResponseFixture();

    $response->update(['responded_at' => now()]);

    $this->get(route('public.stage-response', ['token' => $rawToken]))
        ->assertOk()
        ->assertSee(__('beneficiary_stage.already_title'));
});

it('records a note, advances the aid, and marks the response as used on submit', function () {
    [$aid, $response, $rawToken] = beneficiaryResponseFixture();

    Livewire::test(PublicPage::class, ['token' => $rawToken])
        ->set('note', 'أوافق على استكمال الطلب')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSet('view', 'success');

    $response->refresh();
    expect($response->responded_at)->not->toBeNull()
        ->and($response->note)->toBe('أوافق على استكمال الطلب');

    // It was the last stage, so responding finalises the aid to Approved.
    expect($aid->fresh()->status)->toBe(AidStatus::Approved);
});

it('stores an uploaded document on the response record', function () {
    [, $response, $rawToken] = beneficiaryResponseFixture();

    Livewire::test(PublicPage::class, ['token' => $rawToken])
        ->set('document', UploadedFile::fake()->create('reply.pdf', 100, 'application/pdf'))
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSet('view', 'success');

    $media = $response->fresh()->getMedia('beneficiary_stage_document');

    expect($media)->toHaveCount(1)
        ->and($media->first()->disk)->toBe('local');
});

it('rejects an empty submission with neither note nor document', function () {
    [, , $rawToken] = beneficiaryResponseFixture();

    Livewire::test(PublicPage::class, ['token' => $rawToken])
        ->call('submit')
        ->assertHasErrors('note');
});

it('is idempotent: a second submit on an already-used token does not double-advance', function () {
    [$aid, $response, $rawToken] = beneficiaryResponseFixture();

    // Mark responded already, aid still parked at the stage.
    $response->update(['responded_at' => now()]);

    Livewire::test(PublicPage::class, ['token' => $rawToken])
        ->assertSet('view', 'already');

    // The aid was never advanced by re-opening a used link.
    expect($aid->fresh()->status)->toBe(AidStatus::UnderReview);
});

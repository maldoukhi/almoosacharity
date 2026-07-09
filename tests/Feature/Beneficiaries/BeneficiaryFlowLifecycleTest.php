<?php

use App\Actions\BeneficiaryFlows\DeactivateBeneficiary;
use App\Actions\BeneficiaryFlows\SubmitBeneficiary;
use App\Enums\BeneficiaryStatus;
use App\Exceptions\InvalidBeneficiaryTransitionException;
use App\Livewire\Beneficiaries\ReviewInbox;
use App\Livewire\Beneficiaries\Show as BeneficiaryShow;
use App\Models\Beneficiary;
use Livewire\Livewire;

it('submits a new beneficiary into stage 1 of the default flow, snapshotting the flow', function () {
    $researcher = asResearcher();
    $flow = seedBeneficiaryFlow();
    $firstStage = $flow->stages()->where('order', 1)->firstOrFail();

    $beneficiary = Beneficiary::factory()->create(['status' => BeneficiaryStatus::New]);

    app(SubmitBeneficiary::class)->handle($beneficiary, $researcher);

    $beneficiary->refresh();

    expect($beneficiary->status)->toBe(BeneficiaryStatus::UnderReview);
    expect($beneficiary->beneficiary_flow_id)->toBe($flow->id);
    expect($beneficiary->current_stage_id)->toBe($firstStage->id);
    expect($beneficiary->submitted_at)->not->toBeNull();
});

it('submits a beneficiary for review through the Show component action', function () {
    asResearcher();
    seedBeneficiaryFlow();

    $beneficiary = Beneficiary::factory()->create(['status' => BeneficiaryStatus::New]);

    Livewire::test(BeneficiaryShow::class, ['beneficiary' => $beneficiary])
        ->call('submitForReview');

    expect($beneficiary->fresh()->status)->toBe(BeneficiaryStatus::UnderReview);
});

it('refuses to submit a beneficiary that is not in a submittable state', function () {
    $researcher = asResearcher();
    seedBeneficiaryFlow();

    $beneficiary = Beneficiary::factory()->create(['status' => BeneficiaryStatus::Active]);

    expect(fn () => app(SubmitBeneficiary::class)->handle($beneficiary, $researcher))
        ->toThrow(InvalidBeneficiaryTransitionException::class);
});

it('deactivates an active beneficiary and marks it as blocked from new aids', function () {
    $manager = asManager();
    seedBeneficiaryFlow();

    $beneficiary = Beneficiary::factory()->create(['status' => BeneficiaryStatus::Active]);

    app(DeactivateBeneficiary::class)->handle($beneficiary, $manager);

    $beneficiary->refresh();

    expect($beneficiary->status)->toBe(BeneficiaryStatus::Deactivated);
    // The aid-create path reads the beneficiary's status; a deactivated
    // beneficiary reports isDeactivated() so it can be blocked from new aids.
    expect($beneficiary->status->isDeactivated())->toBeTrue();
});

it('suspends then reactivates a beneficiary via the off-sequence action', function () {
    $manager = asManager();
    seedBeneficiaryFlow();

    $beneficiary = Beneficiary::factory()->create(['status' => BeneficiaryStatus::Active]);

    app(DeactivateBeneficiary::class)->suspend($beneficiary, $manager);
    expect($beneficiary->fresh()->status)->toBe(BeneficiaryStatus::Suspended);

    app(DeactivateBeneficiary::class)->reactivate($beneficiary->fresh(), $manager);
    expect($beneficiary->fresh()->status)->toBe(BeneficiaryStatus::Active);
});

it('refuses an invalid off-sequence transition (deactivating an already deactivated beneficiary)', function () {
    $manager = asManager();
    seedBeneficiaryFlow();

    $beneficiary = Beneficiary::factory()->create(['status' => BeneficiaryStatus::Deactivated]);

    expect(fn () => app(DeactivateBeneficiary::class)->handle($beneficiary, $manager))
        ->toThrow(InvalidBeneficiaryTransitionException::class);
});

it('lists a beneficiary awaiting the researcher in the review inbox, but not one at the manager stage', function () {
    asResearcher();

    $atResearcher = underReviewBeneficiaryAtStage(1);
    $atManager = underReviewBeneficiaryAtStage(2);

    Livewire::test(ReviewInbox::class)
        ->assertSee($atResearcher->full_name)
        ->assertDontSee($atManager->full_name);
});

it('forbids a user without beneficiaries.review from opening the review inbox', function () {
    asDataEntry();

    Livewire::test(ReviewInbox::class)->assertForbidden();
});

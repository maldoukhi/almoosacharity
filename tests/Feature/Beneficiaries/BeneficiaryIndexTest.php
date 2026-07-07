<?php

use App\Actions\Beneficiaries\CreateBeneficiary;
use App\Livewire\Beneficiaries\Index;
use Livewire\Livewire;

it('never leaks the plaintext iban into the beneficiaries index response', function () {
    // Bug found while writing this test (documented for the security
    // review): resources/views/livewire/beneficiaries/index.blade.php has
    // `@can('restore', \App\Models\Beneficiary::class)` — passing the
    // *class name* to an ability whose Policy method
    // (BeneficiaryPolicy::restore(User $user, Beneficiary $beneficiary))
    // only supports a model *instance*, never a class-level check. Gate
    // resolves the class-string form by stripping it as the "policy class
    // hint" argument, leaving restore() called with just $user — an
    // ArgumentCountError. Spatie's permission Gate::before only
    // short-circuits to *true* (never to false), so this line only avoids
    // crashing for actors who already hold beneficiaries.restore (in
    // practice: system-admin only, via the unrelated admin bypass). Every
    // other role gets a fatal error rendering this page at all, not just
    // the trashed-list toggle. Using asAdmin() here works around the bug
    // so this test can still exercise the iban-leak behavior it targets;
    // see BeneficiarySoftDeleteTest for the same workaround plus a direct
    // (non-blade) assertion of the underlying authorization boundary.
    $actor = asAdmin();

    $plainIban = validSaudiIban();

    app(CreateBeneficiary::class)->handle(
        beneficiaryAttributes([
            'national_id' => '1999888777',
            'iban' => $plainIban,
            'bank_name' => 'بنك الرياض',
            'bank_account_holder' => 'محمد العتيبي الحساس',
        ]),
        [],
        $actor,
    );

    Livewire::test(Index::class)
        ->assertSee('العتيبي') // sanity check: the beneficiary row is present
        ->assertDontSee($plainIban)
        ->assertDontSee('محمد العتيبي الحساس');
});

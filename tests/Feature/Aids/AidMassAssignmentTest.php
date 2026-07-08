<?php

use App\Actions\Aids\CreateAid;
use App\Enums\AidProgramType;
use App\Enums\AidStatus;
use App\Enums\AidType;
use App\Livewire\Aids\Form;
use App\Models\Aid;
use App\Models\AidProgram;
use App\Models\Beneficiary;
use Livewire\Exceptions\PublicPropertyNotFoundException;
use Livewire\Livewire;

/*
|--------------------------------------------------------------------------
| Status mass-assignment protection
|--------------------------------------------------------------------------
|
| App\Models\Aid marks `status` as fillable (see #[Fillable(...)]), so the
| only thing standing between a user with aids.create and directly setting
| an aid's status is every Action's explicit, hand-picked attribute list
| (CreateAid/UpdateAid never spread the raw input array into Aid::create/
| update). This is a defense-in-depth gap worth flagging in the security
| review: a future Action that naively does `Aid::create($data)` would
| reopen it. Both layers are exercised below.
*/

it('ignores a status key passed straight to the CreateAid action', function () {
    seedAidCatalog();
    $actor = asDataEntry();

    $program = AidProgram::query()->where('type', AidProgramType::Cash)->firstOrFail();
    $beneficiary = Beneficiary::factory()->create();

    $aid = app(CreateAid::class)->handle([
        'beneficiary_id' => $beneficiary->id,
        'aid_program_id' => $program->id,
        'type' => AidType::Cash->value,
        'amount' => 500,
        'purpose' => 'اختبار',
        // Attempted injection: not part of CreateAid's documented input
        // shape at all.
        'status' => AidStatus::Approved->value,
    ], $actor);

    expect($aid->status)->toBe(AidStatus::Draft);
});

it('cannot change an aid\'s status via a raw Livewire set() on the create form', function () {
    seedAidCatalog();
    asDataEntry();

    $program = AidProgram::query()->where('type', AidProgramType::Cash)->firstOrFail();
    $beneficiary = Beneficiary::factory()->create();

    $component = Livewire::test(Form::class)
        ->set('beneficiary_ids', [$beneficiary->id])
        ->set('aid_program_id', $program->id)
        ->set('type', AidType::Cash->value)
        ->set('amount', 650)
        ->set('purpose', 'اختبار الحماية');

    // Form has no public $status property at all, so Livewire itself
    // refuses the update outright rather than silently dropping it —
    // an even stronger guarantee than "the value is ignored".
    expect(fn () => $component->set('status', AidStatus::Approved->value))
        ->toThrow(PublicPropertyNotFoundException::class);

    $component->call('save');

    $created = Aid::query()->where('purpose', 'اختبار الحماية')->firstOrFail();

    expect($created->status)->toBe(AidStatus::Draft);
});

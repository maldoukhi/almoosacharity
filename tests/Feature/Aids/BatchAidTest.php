<?php

use App\Enums\AidProgramType;
use App\Enums\AidStatus;
use App\Enums\AidType;
use App\Enums\BeneficiaryStatus;
use App\Livewire\Aids\BatchCreate;
use App\Models\Aid;
use App\Models\AidBatch;
use App\Models\AidProgram;
use App\Models\Beneficiary;
use App\Models\BeneficiaryCategory;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

it('creates one cash aid per beneficiary in a category, honouring per-beneficiary amount overrides', function () {
    seedAidCatalog();
    $actor = asDataEntry();

    $program = AidProgram::query()->where('type', AidProgramType::Cash)->firstOrFail();
    $category = BeneficiaryCategory::create(['name' => 'أرملة', 'is_active' => true, 'sort_order' => 1]);

    $beneficiaries = Beneficiary::factory()->count(3)->create(['status' => BeneficiaryStatus::Active]);
    foreach ($beneficiaries as $beneficiary) {
        $beneficiary->categories()->attach($category->id);
    }

    [$first, $second, $third] = $beneficiaries;

    Livewire::test(BatchCreate::class)
        ->set('category_ids', [$category->id])
        ->set('aid_program_id', $program->id)
        ->set('mode', AidType::Cash->value)
        ->set('default_amount', 500)
        ->set('default_purpose', 'سلة غذائية')
        ->set('selected_ids', $beneficiaries->pluck('id')->all())
        ->set('overrideAmounts', [$first->id => 1234])
        ->call('create')
        ->assertHasNoErrors()
        ->assertDispatched('toast', type: 'success');

    $aids = Aid::query()->where('aid_program_id', $program->id)->get();

    expect($aids)->toHaveCount(3);
    $aids->each(function (Aid $aid) use ($program, $actor) {
        expect($aid->type)->toBe(AidType::Cash);
        expect($aid->status)->toBe(AidStatus::Draft);
        expect($aid->aid_program_id)->toBe($program->id);
        expect($aid->created_by)->toBe($actor->id);
    });

    // The overridden beneficiary gets 1234; the rest fall back to the default 500.
    expect((float) $aids->firstWhere('beneficiary_id', $first->id)->amount)->toBe(1234.0);
    expect((float) $aids->firstWhere('beneficiary_id', $second->id)->amount)->toBe(500.0);
    expect((float) $aids->firstWhere('beneficiary_id', $third->id)->amount)->toBe(500.0);

    // Every aid is linked to the batch through the pivot.
    $batch = AidBatch::query()->firstOrFail();
    expect($batch->program_id)->toBe($program->id);
    expect($batch->aids()->count())->toBe(3);
});

it('creates both a cash and an in-kind aid per beneficiary in "both" mode, all linked to the batch pivot', function () {
    seedAidCatalog();
    asDataEntry();

    $program = AidProgram::query()->where('type', AidProgramType::Both)->firstOrFail();
    $category = BeneficiaryCategory::create(['name' => 'أرملة', 'is_active' => true, 'sort_order' => 1]);

    $beneficiaries = Beneficiary::factory()->count(2)->create(['status' => BeneficiaryStatus::Active]);
    foreach ($beneficiaries as $beneficiary) {
        $beneficiary->categories()->attach($category->id);
    }

    Livewire::test(BatchCreate::class)
        ->set('category_ids', [$category->id])
        ->set('aid_program_id', $program->id)
        ->set('mode', 'both')
        ->set('default_amount', 700)
        ->set('default_purpose', 'دعم عاجل')
        ->set('default_items', [
            ['name' => 'بطانية', 'quantity' => 2, 'estimated_value' => null, 'description' => ''],
        ])
        ->set('selected_ids', $beneficiaries->pluck('id')->all())
        ->call('create')
        ->assertHasNoErrors();

    $aids = Aid::query()->where('aid_program_id', $program->id)->with('items')->get();

    // 2 beneficiaries × (1 cash + 1 in-kind) = 4 aids.
    expect($aids)->toHaveCount(4);
    expect($aids->where('type', AidType::Cash))->toHaveCount(2);
    expect($aids->where('type', AidType::InKind))->toHaveCount(2);

    foreach ($beneficiaries as $beneficiary) {
        $forBeneficiary = $aids->where('beneficiary_id', $beneficiary->id);
        expect($forBeneficiary)->toHaveCount(2);
        expect($forBeneficiary->pluck('type')->map->value->sort()->values()->all())
            ->toEqual([AidType::Cash->value, AidType::InKind->value]);
    }

    // In-kind aids carry the default item list.
    $aids->where('type', AidType::InKind)->each(function (Aid $aid) {
        expect($aid->items)->toHaveCount(1);
        expect($aid->items->first()->name)->toBe('بطانية');
    });

    // The pivot links all four aids to the single batch.
    $batch = AidBatch::query()->firstOrFail();
    expect($batch->aids()->count())->toBe(4);
    expect($batch->aids()->pluck('aids.id')->sort()->values()->all())
        ->toEqual($aids->pluck('id')->sort()->values()->all());
});

it('matches beneficiaries by national id from an uploaded Excel/CSV and reports the unmatched ones', function () {
    seedAidCatalog();
    asDataEntry();

    $matchA = Beneficiary::factory()->create(['status' => BeneficiaryStatus::Active, 'national_id' => '1000000001']);
    $matchB = Beneficiary::factory()->create(['status' => BeneficiaryStatus::Active, 'national_id' => '1000000002']);

    // A well-formed national id that belongs to nobody.
    $csv = "national_id\n1000000001\n1000000002\n1999999999\n";
    $file = UploadedFile::fake()->createWithContent('ids.csv', $csv);

    $component = Livewire::test(BatchCreate::class)
        ->set('nationalIdFile', $file)
        ->assertHasNoErrors();

    $component->assertSet('selected_ids', fn (array $ids) => count($ids) === 2
        && in_array($matchA->id, $ids, true)
        && in_array($matchB->id, $ids, true));

    $component->assertSet('unmatchedNationalIds', ['1999999999']);
});

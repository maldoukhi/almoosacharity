<?php

use App\Livewire\Beneficiaries\Import;
use App\Models\Beneficiary;
use App\Models\BeneficiaryCategory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

/**
 * Header order used across these fixtures. Column indexes the mapping refers
 * to are the 0-based positions in this list.
 */
function importHeader(): string
{
    return "id,first,last,phone,sex,marital,housing,cats\n";
}

/**
 * The per-column mapping (column index => beneficiary field) matching
 * {@see importHeader()}. The redesigned importer maps from the column side.
 *
 * @return array<int, string>
 */
function importColumnMapping(): array
{
    return [
        0 => 'national_id',
        1 => 'first_name',
        2 => 'last_name',
        3 => 'mobile',
        4 => 'gender',
        5 => 'marital_status',
        6 => 'housing_type',
        7 => 'categories',
    ];
}

/**
 * Apply the standard column mapping to a review-step component.
 */
function applyImportMapping($component)
{
    foreach (importColumnMapping() as $column => $field) {
        $component->set("columnMapping.{$column}", $field);
    }

    return $component;
}

function uploadCsv(string $body): UploadedFile
{
    return UploadedFile::fake()->createWithContent('beneficiaries.csv', importHeader().$body);
}

beforeEach(function () {
    Storage::fake('local');
});

it('advances to the review step and loads every data row on upload', function () {
    asDataEntry();

    $file = uploadCsv(
        "1234567890,محمد,العتيبي,0512345678,male,married,owned,\n".
        "2234567890,سارة,القحطاني,0533221100,female,single,rented,\n"
    );

    $component = Livewire::test(Import::class)
        ->set('file', $file)
        ->assertSet('step', 'review');

    expect($component->get('rows'))->toHaveCount(2);
    // Real spreadsheet line numbers are preserved (header = line 1).
    expect($component->get('rows')[0]['line'])->toBe(2);
    expect($component->get('rows')[1]['line'])->toBe(3);
});

it('imports valid rows and creates beneficiaries from the mapped sheet', function () {
    asDataEntry();

    $file = uploadCsv(
        "1234567890,محمد,العتيبي,0512345678,male,married,owned,\n".
        "2234567890,سارة,القحطاني,0533221100,female,single,rented,\n"
    );

    $component = applyImportMapping(Livewire::test(Import::class)->set('file', $file));

    $component->call('import')->assertSet('step', 'done');

    expect(Beneficiary::count())->toBe(2);
    expect(Beneficiary::where('national_id', '1234567890')->first())
        ->first_name->toBe('محمد')
        ->last_name->toBe('العتيبي')
        ->mobile->toBe('0512345678')
        ->gender->value->toBe('male')
        ->housing_type->value->toBe('owned');

    $component->assertSet('summary.created', 2)
        ->assertSet('summary.duplicates', 0)
        ->assertSet('summary.file_duplicates', 0)
        ->assertSet('summary.excluded', 0);

    expect($component->get('summary')['errors'])->toBeEmpty();
});

it('flags invalid rows and reports them without aborting the batch', function () {
    asDataEntry();

    $file = uploadCsv(
        "1234567890,محمد,العتيبي,0512345678,male,married,owned,\n".
        "notavalidid,خالد,الشهري,0511111111,male,single,owned,\n".      // bad national id
        "2234567890,نورة,الدوسري,123,female,widowed,rented,\n"          // bad mobile
    );

    $component = applyImportMapping(Livewire::test(Import::class)->set('file', $file));

    $component->call('import')->assertSet('step', 'done');

    // Only the first row is valid.
    expect(Beneficiary::count())->toBe(1);
    expect(Beneficiary::where('national_id', '1234567890')->exists())->toBeTrue();

    $summary = $component->get('summary');
    expect($summary['created'])->toBe(1);
    expect($summary['errors'])->toHaveCount(2);
    // Errors point at the 1-based spreadsheet line numbers (header = 1).
    expect(collect($summary['errors'])->pluck('row')->all())->toBe([3, 4]);
});

it('lets the admin fix an invalid cell inline and then imports the fixed row', function () {
    asDataEntry();

    $file = uploadCsv("notavalidid,محمد,العتيبي,0512345678,male,married,owned,\n");

    $component = applyImportMapping(Livewire::test(Import::class)->set('file', $file));

    // As-uploaded the only row is invalid, so nothing is ready to import.
    expect(Beneficiary::count())->toBe(0);

    // Correct the offending national-id cell inline, then import.
    $component->set('rows.0.cells.0', '1234567890');

    $component->call('import')->assertSet('step', 'done');

    expect(Beneficiary::count())->toBe(1);
    expect(Beneficiary::where('national_id', '1234567890')->exists())->toBeTrue();
    $component->assertSet('summary.created', 1);
    expect($component->get('summary')['errors'])->toBeEmpty();
});

it('excludes a row the admin unchecks from the import', function () {
    asDataEntry();

    $file = uploadCsv(
        "1234567890,محمد,العتيبي,0512345678,male,married,owned,\n".
        "2234567890,سارة,القحطاني,0533221100,female,single,rented,\n"
    );

    $component = applyImportMapping(Livewire::test(Import::class)->set('file', $file));

    // Exclude the second row.
    $component->set('included.1', false);

    $component->call('import')->assertSet('step', 'done');

    expect(Beneficiary::count())->toBe(1);
    expect(Beneficiary::where('national_id', '1234567890')->exists())->toBeTrue();
    expect(Beneficiary::where('national_id', '2234567890')->exists())->toBeFalse();

    $component->assertSet('summary.created', 1)
        ->assertSet('summary.excluded', 1);
});

it('excludes a whole group at once', function () {
    asDataEntry();

    $file = uploadCsv(
        "1234567890,محمد,العتيبي,0512345678,male,married,owned,\n".
        "1234567899,خالد,الشهري,0512345670,male,single,rented,\n".
        "2234567890,سارة,القحطاني,0533221100,female,single,rented,\n"
    );

    $component = applyImportMapping(Livewire::test(Import::class)->set('file', $file));

    // Group by the gender column (index 4), then exclude the whole "male" group.
    $component->set('groupBy', '4');
    $component->call('setGroupIncluded', 'male', false);

    $component->call('import')->assertSet('step', 'done');

    // Only the single female row survives the group exclusion.
    expect(Beneficiary::count())->toBe(1);
    expect(Beneficiary::where('national_id', '2234567890')->exists())->toBeTrue();
    expect(Beneficiary::where('national_id', '1234567890')->exists())->toBeFalse();

    $component->assertSet('summary.created', 1)
        ->assertSet('summary.excluded', 2);
});

it('flags a national id already in the system and does not duplicate it', function () {
    asDataEntry();

    Beneficiary::factory()->create(['national_id' => '1234567890']);

    $file = uploadCsv(
        "1234567890,محمد,العتيبي,0512345678,male,married,owned,\n".
        "2234567890,سارة,القحطاني,0533221100,female,single,rented,\n"
    );

    $component = applyImportMapping(Livewire::test(Import::class)->set('file', $file));

    $component->call('import')->assertSet('step', 'done');

    // The existing national id is skipped, not recreated: one existing + one new.
    expect(Beneficiary::count())->toBe(2);
    expect(Beneficiary::where('national_id', '1234567890')->count())->toBe(1);

    $component->assertSet('summary.created', 1)
        ->assertSet('summary.duplicates', 1);
});

it('catches duplicate national ids within the uploaded file', function () {
    asDataEntry();

    // Same national id twice; the first imports, the second is flagged.
    $file = uploadCsv(
        "1234567890,محمد,العتيبي,0512345678,male,married,owned,\n".
        "1234567890,محمد,العتيبي,0512345678,male,married,owned,\n".
        "2234567890,سارة,القحطاني,0533221100,female,single,rented,\n"
    );

    $component = applyImportMapping(Livewire::test(Import::class)->set('file', $file));

    $component->call('import')->assertSet('step', 'done');

    expect(Beneficiary::count())->toBe(2);
    expect(Beneficiary::where('national_id', '1234567890')->count())->toBe(1);

    $component->assertSet('summary.created', 2)
        ->assertSet('summary.file_duplicates', 1);
});

it('attaches existing categories matched by name', function () {
    asDataEntry();

    $widow = BeneficiaryCategory::create(['name' => 'أرملة', 'is_active' => true, 'sort_order' => 1]);

    $file = uploadCsv("1234567890,محمد,العتيبي,0512345678,male,married,owned,أرملة\n");

    $component = applyImportMapping(Livewire::test(Import::class)->set('file', $file));

    $component->call('import')->assertSet('step', 'done');

    $beneficiary = Beneficiary::where('national_id', '1234567890')->first();

    expect($beneficiary)->not->toBeNull();
    expect($beneficiary->categories->pluck('id')->all())->toBe([$widow->id]);
});

it('requires the mandatory fields to be mapped before importing', function () {
    asDataEntry();

    $file = uploadCsv("1234567890,محمد,العتيبي,0512345678,male,married,owned,\n");

    Livewire::test(Import::class)
        ->set('file', $file)
        ->assertSet('step', 'review')
        // No mapping set for the required fields.
        ->call('import')
        ->assertHasErrors(['mapping.national_id'])
        ->assertSet('step', 'review');

    expect(Beneficiary::count())->toBe(0);
});

it('normalizes an internationally formatted mobile number on import', function () {
    asDataEntry();

    $file = uploadCsv("1234567890,محمد,العتيبي,+966512345678,male,married,owned,\n");

    $component = applyImportMapping(Livewire::test(Import::class)->set('file', $file));

    $component->call('import');

    expect(Beneficiary::where('national_id', '1234567890')->first()->mobile)->toBe('0512345678');
});

it('denies access to a user without the beneficiaries.import permission', function () {
    asManager();

    Livewire::test(Import::class)->assertForbidden();
});

<?php

use App\Livewire\Beneficiaries\Import;
use App\Models\Beneficiary;
use App\Models\BeneficiaryCategory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

/**
 * Header order used across these fixtures. Column indexes the mapping
 * refers to are the 0-based positions in this list.
 */
function importHeader(): string
{
    return "id,first,last,phone,sex,marital,housing,cats\n";
}

/**
 * The 8 mapping choices matching {@see importHeader()} column order.
 *
 * @return array<string, string>
 */
function importMapping(): array
{
    return [
        'national_id' => '0',
        'first_name' => '1',
        'last_name' => '2',
        'mobile' => '3',
        'gender' => '4',
        'marital_status' => '5',
        'housing_type' => '6',
        'categories' => '7',
    ];
}

function uploadCsv(string $body): UploadedFile
{
    return UploadedFile::fake()->createWithContent('beneficiaries.csv', importHeader().$body);
}

beforeEach(function () {
    Storage::fake('local');
});

it('imports valid rows and creates beneficiaries from a mapped sheet', function () {
    asDataEntry();

    $file = uploadCsv(
        "1234567890,محمد,العتيبي,0512345678,male,married,owned,\n".
        "2234567890,سارة,القحطاني,0533221100,female,single,rented,\n"
    );

    $component = Livewire::test(Import::class)
        ->set('file', $file)
        ->assertSet('step', 'map');

    foreach (importMapping() as $field => $column) {
        $component->set("mapping.{$field}", $column);
    }

    $component->call('import')->assertSet('step', 'done');

    expect(Beneficiary::count())->toBe(2);
    expect(Beneficiary::where('national_id', '1234567890')->first())
        ->first_name->toBe('محمد')
        ->last_name->toBe('العتيبي')
        ->mobile->toBe('0512345678')
        ->gender->value->toBe('male')
        ->housing_type->value->toBe('owned');

    $component->assertSet('summary.created', 2)
        ->assertSet('summary.duplicates', 0);

    expect($component->get('summary')['errors'])->toBeEmpty();
});

it('skips invalid rows and reports them without aborting the batch', function () {
    asDataEntry();

    $file = uploadCsv(
        "1234567890,محمد,العتيبي,0512345678,male,married,owned,\n".
        "notavalidid,خالد,الشهري,0511111111,male,single,owned,\n".      // bad national id
        "2234567890,نورة,الدوسري,123,female,widowed,rented,\n"          // bad mobile
    );

    $component = Livewire::test(Import::class)->set('file', $file);

    foreach (importMapping() as $field => $column) {
        $component->set("mapping.{$field}", $column);
    }

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

it('skips a row whose national id already exists', function () {
    asDataEntry();

    Beneficiary::factory()->create(['national_id' => '1234567890']);

    $file = uploadCsv(
        "1234567890,محمد,العتيبي,0512345678,male,married,owned,\n".
        "2234567890,سارة,القحطاني,0533221100,female,single,rented,\n"
    );

    $component = Livewire::test(Import::class)->set('file', $file);

    foreach (importMapping() as $field => $column) {
        $component->set("mapping.{$field}", $column);
    }

    $component->call('import')->assertSet('step', 'done');

    // The duplicate is skipped; only the new national id is created.
    expect(Beneficiary::count())->toBe(2);

    $summary = $component->get('summary');
    expect($summary['created'])->toBe(1);
    expect($summary['duplicates'])->toBe(1);
});

it('attaches existing categories matched by name', function () {
    asDataEntry();

    $widow = BeneficiaryCategory::create(['name' => 'أرملة', 'is_active' => true, 'sort_order' => 1]);

    $file = uploadCsv("1234567890,محمد,العتيبي,0512345678,male,married,owned,أرملة\n");

    $component = Livewire::test(Import::class)->set('file', $file);

    foreach (importMapping() as $field => $column) {
        $component->set("mapping.{$field}", $column);
    }

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
        ->assertSet('step', 'map')
        // No mapping set for the required fields.
        ->call('import')
        ->assertHasErrors(['mapping.national_id'])
        ->assertSet('step', 'map');

    expect(Beneficiary::count())->toBe(0);
});

it('normalizes an internationally formatted mobile number on import', function () {
    asDataEntry();

    $file = uploadCsv("1234567890,محمد,العتيبي,+966512345678,male,married,owned,\n");

    $component = Livewire::test(Import::class)->set('file', $file);

    foreach (importMapping() as $field => $column) {
        $component->set("mapping.{$field}", $column);
    }

    $component->call('import');

    expect(Beneficiary::where('national_id', '1234567890')->first()->mobile)->toBe('0512345678');
});

it('denies access to a user without the beneficiaries.import permission', function () {
    asManager();

    Livewire::test(Import::class)->assertForbidden();
});

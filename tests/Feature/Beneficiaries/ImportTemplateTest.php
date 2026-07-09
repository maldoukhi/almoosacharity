<?php

use App\Actions\Beneficiaries\ImportBeneficiaries;
use App\Exports\BeneficiaryImportTemplateExport;
use App\Livewire\Beneficiaries\Import;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('local');
});

it('exposes the canonical header for every importable field, in the importer\'s field order', function () {
    $headers = BeneficiaryImportTemplateExport::headers();

    expect(array_keys($headers))->toBe(ImportBeneficiaries::FIELDS);

    // The exact Arabic labels the smart importer already recognizes.
    expect($headers)->toBe([
        'national_id' => 'رقم الهوية',
        'first_name' => 'الاسم الأول',
        'second_name' => 'الاسم الثاني',
        'third_name' => 'الاسم الثالث',
        'last_name' => 'اسم العائلة',
        'mobile' => 'رقم الجوال',
        'nationality' => 'الجنسية',
        'birth_date' => 'تاريخ الميلاد',
        'gender' => 'الجنس',
        'marital_status' => 'الحالة الاجتماعية',
        'occupation' => 'المهنة',
        'employer' => 'جهة العمل',
        'monthly_income' => 'الدخل الشهري',
        'health_status' => 'الحالة الصحية',
        'special_needs' => 'الاحتياجات الخاصة',
        'housing_type' => 'نوع السكن',
        'city' => 'المدينة',
        'district' => 'الحي',
        'national_address' => 'العنوان الوطني',
        'categories' => 'التصنيفات',
        'notes' => 'ملاحظات',
    ]);
});

it('renders headings, one example row, and a required-fields note row', function () {
    $export = new BeneficiaryImportTemplateExport;

    $headings = $export->headings();
    $rows = $export->array();

    expect($headings)->toBe(array_values(BeneficiaryImportTemplateExport::headers()));
    expect($headings)->toHaveCount(count(ImportBeneficiaries::FIELDS));

    // Row 1: a realistic example, same column count as the headings.
    expect($rows[0])->toHaveCount(count($headings));
    expect($rows[0][0])->toBe('1234567890'); // national_id example

    // Row 2: the required-fields note, in the first cell only.
    expect($rows[1][0])->toContain('رقم الهوية');
    expect(array_slice($rows[1], 1))->each->toBe('');
});

it('lets an authorized user download the official template as an excel file', function () {
    asDataEntry();

    Livewire::test(Import::class)
        ->call('downloadTemplate')
        ->assertFileDownloaded('beneficiary-import-template.xlsx');
});

it('forbids downloading the template without the beneficiaries.import permission', function () {
    asManager();

    Livewire::test(Import::class)->assertForbidden();
});

it('auto-fills the column mapping when the uploaded header row matches the official template', function () {
    asDataEntry();

    $headerRow = implode(',', array_values(BeneficiaryImportTemplateExport::headers()));
    $dataRow = '1234567890,محمد,,,العتيبي,0512345678,SA,1990-01-01,male,married,,,,,,owned,الرياض,,,,';

    $file = UploadedFile::fake()->createWithContent('official-template.csv', $headerRow."\n".$dataRow."\n");

    $component = Livewire::test(Import::class)
        ->set('file', $file)
        ->assertSet('step', 'review')
        ->assertSet('officialTemplateDetected', true);

    $mapping = $component->get('columnMapping');

    foreach (array_keys(BeneficiaryImportTemplateExport::headers()) as $column => $field) {
        expect($mapping[$column])->toBe($field);
    }

    // Every required field is therefore already mapped, with no manual work.
    expect($component->instance()->requiredMapped())->toBeTrue();
});

it('does not flag an unrelated file as the official template and leaves it to fuzzy auto-guessing', function () {
    asDataEntry();

    $file = UploadedFile::fake()->createWithContent(
        'custom.csv',
        "id,first,last,phone,sex,marital,housing,cats\n1234567890,محمد,العتيبي,0512345678,male,married,owned,\n"
    );

    Livewire::test(Import::class)
        ->set('file', $file)
        ->assertSet('step', 'review')
        ->assertSet('officialTemplateDetected', false);
});

it('recognizes the template even with case and stray-whitespace differences in the header row', function () {
    asDataEntry();

    $headers = array_values(BeneficiaryImportTemplateExport::headers());
    $headers[0] = '  '.$headers[0].'  '; // stray whitespace around "رقم الهوية"

    $headerRow = implode(',', $headers);
    $dataRow = '1234567890,محمد,,,العتيبي,0512345678,SA,1990-01-01,male,married,,,,,,owned,الرياض,,,,';

    $file = UploadedFile::fake()->createWithContent('official-template-spaced.csv', $headerRow."\n".$dataRow."\n");

    Livewire::test(Import::class)
        ->set('file', $file)
        ->assertSet('officialTemplateDetected', true);
});

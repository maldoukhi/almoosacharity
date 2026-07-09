<?php

use App\Enums\AidProgramType;
use App\Enums\AidType;
use App\Livewire\Aids\Form;
use App\Models\Aid;
use App\Models\AidProgram;
use App\Models\Beneficiary;
use Database\Factories\AidFactory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('local');
});

it('stores uploaded documents to the private aid_documents collection on create', function () {
    seedAidCatalog();
    $actor = asDataEntry();

    $program = AidProgram::query()->where('type', AidProgramType::Cash)->firstOrFail();
    $beneficiary = Beneficiary::factory()->create();

    Livewire::test(Form::class)
        ->set('beneficiary_ids', [$beneficiary->id])
        ->set('aid_program_id', $program->id)
        ->set('type', AidType::Cash->value)
        ->set('amount', 640)
        ->set('purpose', 'إعانة')
        ->set('documents', [
            UploadedFile::fake()->create('quote.pdf', 120, 'application/pdf'),
            UploadedFile::fake()->image('receipt.png'),
        ])
        ->call('save')
        ->assertHasNoErrors();

    $aid = Aid::query()->where('created_by', $actor->id)->latest('id')->firstOrFail();
    $media = $aid->getMedia('aid_documents');

    expect($media)->toHaveCount(2)
        ->and($media->first()->disk)->toBe('local')
        ->and($media->pluck('file_name')->all())->toContain('quote.pdf');
});

it('attaches documents when editing an existing draft aid', function () {
    seedAidCatalog();
    $actor = asResearcher();

    $program = AidProgram::query()->where('type', AidProgramType::Cash)->firstOrFail();
    $aid = AidFactory::new()->draft()->create([
        'beneficiary_id' => Beneficiary::factory()->create()->id,
        'aid_program_id' => $program->id,
        'type' => AidType::Cash,
        'amount' => 900,
        'purpose' => 'دعم',
        'created_by' => $actor->id,
    ]);

    Livewire::test(Form::class, ['aid' => $aid])
        ->set('documents', [UploadedFile::fake()->create('invoice.pdf', 90, 'application/pdf')])
        ->call('save')
        ->assertHasNoErrors();

    expect($aid->fresh()->getMedia('aid_documents'))->toHaveCount(1);
});

it('rejects a document whose type is not pdf/jpg/png', function () {
    seedAidCatalog();
    asDataEntry();

    $program = AidProgram::query()->where('type', AidProgramType::Cash)->firstOrFail();
    $beneficiary = Beneficiary::factory()->create();

    Livewire::test(Form::class)
        ->set('beneficiary_ids', [$beneficiary->id])
        ->set('aid_program_id', $program->id)
        ->set('type', AidType::Cash->value)
        ->set('amount', 300)
        ->set('purpose', 'دعم')
        ->set('documents', [UploadedFile::fake()->create('malware.exe', 10, 'application/octet-stream')])
        ->call('save')
        ->assertHasErrors('documents.*');
});

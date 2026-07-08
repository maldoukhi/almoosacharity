<?php

use App\Enums\AidProgramType;
use App\Enums\AidType;
use App\Livewire\Settings\AidPrograms\Index;
use App\Models\AidProgram;
use App\Models\Beneficiary;
use Database\Factories\AidFactory;
use Livewire\Livewire;

it('refuses to delete an aid program that still has aids attached to it', function () {
    // settings.manage is reserved to system-admin alone.
    asAdmin();
    seedAidCatalog();

    $program = AidProgram::query()->where('type', AidProgramType::Cash)->firstOrFail();

    AidFactory::new()->draft()->create([
        'beneficiary_id' => Beneficiary::factory()->create()->id,
        'aid_program_id' => $program->id,
        'type' => AidType::Cash,
        'amount' => 500,
    ]);

    Livewire::test(Index::class)
        ->call('delete', $program->id)
        ->assertDispatched('toast', type: 'error');

    expect(AidProgram::query()->find($program->id))->not->toBeNull();
});

it('lets a program with no aids attached be deleted', function () {
    asAdmin();
    seedAidCatalog();

    $program = AidProgram::query()->where('type', AidProgramType::InKind)->firstOrFail();

    Livewire::test(Index::class)
        ->call('delete', $program->id)
        ->assertDispatched('toast', type: 'success');

    expect(AidProgram::withTrashed()->find($program->id)?->trashed())->toBeTrue();
});

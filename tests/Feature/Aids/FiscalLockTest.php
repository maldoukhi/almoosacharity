<?php

use App\Actions\Aids\UpdateAid;
use App\Enums\AidProgramType;
use App\Enums\AidType;
use App\Models\AidProgram;
use App\Models\Beneficiary;
use App\Support\Settings;
use Database\Factories\AidFactory;

it('blocks updating an aid created in a locked fiscal year', function () {
    seedAidCatalog();
    $creator = asDataEntry();

    $program = AidProgram::query()->where('type', AidProgramType::Cash)->firstOrFail();

    $aid = AidFactory::new()->draft()->create([
        'beneficiary_id' => Beneficiary::factory()->create()->id,
        'aid_program_id' => $program->id,
        'type' => AidType::Cash,
        'amount' => 1000,
        'created_by' => $creator->id,
        'created_at' => '2020-05-01',
    ]);

    app(Settings::class)->set('fiscal_locked_until_year', '2020');

    expect(fn () => app(UpdateAid::class)->handle($aid, ['amount' => 1500]))
        ->toThrow(RuntimeException::class);

    expect($aid->fresh()->amount)->toEqual(1000);
});

it('still allows updating a draft aid created after the locked fiscal year', function () {
    seedAidCatalog();
    $creator = asDataEntry();

    $program = AidProgram::query()->where('type', AidProgramType::Cash)->firstOrFail();

    $aid = AidFactory::new()->draft()->create([
        'beneficiary_id' => Beneficiary::factory()->create()->id,
        'aid_program_id' => $program->id,
        'type' => AidType::Cash,
        'amount' => 800,
        'created_by' => $creator->id,
        'created_at' => now(),
    ]);

    // Books closed only through 2020 — a current-year aid stays mutable.
    app(Settings::class)->set('fiscal_locked_until_year', '2020');

    $updated = app(UpdateAid::class)->handle($aid, ['amount' => 1750]);

    expect($updated->amount)->toEqual(1750);
});

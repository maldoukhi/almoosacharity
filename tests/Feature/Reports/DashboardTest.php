<?php

use App\Enums\AidProgramType;
use App\Enums\AidStatus;
use App\Enums\AidType;
use App\Livewire\Dashboard;
use App\Models\AidProgram;
use App\Models\Beneficiary;
use Database\Factories\AidFactory;
use Livewire\Livewire;

it('reflects the actual aid counts per status in aidsByStatus', function () {
    asAdmin();

    seedAidCatalog();
    $program = AidProgram::query()->where('type', AidProgramType::Cash)->firstOrFail();

    AidFactory::new()->draft()->create([
        'beneficiary_id' => Beneficiary::factory()->create()->id,
        'aid_program_id' => $program->id,
        'type' => AidType::Cash,
        'amount' => 100,
    ]);

    AidFactory::new()->approved()->create([
        'beneficiary_id' => Beneficiary::factory()->create()->id,
        'aid_program_id' => $program->id,
        'type' => AidType::Cash,
        'amount' => 200,
    ]);

    AidFactory::new()->approved()->create([
        'beneficiary_id' => Beneficiary::factory()->create()->id,
        'aid_program_id' => $program->id,
        'type' => AidType::Cash,
        'amount' => 300,
    ]);

    AidFactory::new()->rejected()->create([
        'beneficiary_id' => Beneficiary::factory()->create()->id,
        'aid_program_id' => $program->id,
        'type' => AidType::Cash,
        'amount' => 400,
    ]);

    $byStatus = Livewire::test(Dashboard::class)->instance()->aidsByStatus;

    $statuses = AidStatus::cases();
    $labels = array_map(fn (AidStatus $s): string => $s->label(), $statuses);

    $draftIndex = array_search(AidStatus::Draft->label(), $labels, true);
    $approvedIndex = array_search(AidStatus::Approved->label(), $labels, true);
    $rejectedIndex = array_search(AidStatus::Rejected->label(), $labels, true);

    expect($byStatus['series'][$draftIndex])->toBe(1);
    expect($byStatus['series'][$approvedIndex])->toBe(2);
    expect($byStatus['series'][$rejectedIndex])->toBe(1);
    expect(array_sum($byStatus['series']))->toBe(4);
});

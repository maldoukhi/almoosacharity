<?php

use App\Enums\AidProgramType;
use App\Enums\AidType;
use App\Models\AidProgram;
use App\Models\Beneficiary;
use App\Reports\Filters\FinancialFilter;
use App\Reports\FinancialReport;
use Database\Factories\AidFactory;

it('groups approved/delivered aids by program and decision month with correct cash totals', function () {
    asDataEntry();

    seedAidCatalog();
    $program = AidProgram::query()->where('type', AidProgramType::Cash)->firstOrFail();

    // Two approved aids decided in the same month (June): 1000 + 500.
    AidFactory::new()->approved()->create([
        'beneficiary_id' => Beneficiary::factory()->create()->id,
        'aid_program_id' => $program->id,
        'type' => AidType::Cash,
        'amount' => 1000,
        'decided_at' => '2026-06-05',
    ]);

    AidFactory::new()->approved()->create([
        'beneficiary_id' => Beneficiary::factory()->create()->id,
        'aid_program_id' => $program->id,
        'type' => AidType::Cash,
        'amount' => 500,
        'decided_at' => '2026-06-20',
    ]);

    // One aid decided in a later month (July): 2000.
    AidFactory::new()->approved()->create([
        'beneficiary_id' => Beneficiary::factory()->create()->id,
        'aid_program_id' => $program->id,
        'type' => AidType::Cash,
        'amount' => 2000,
        'decided_at' => '2026-07-01',
    ]);

    // A rejected aid also has a decided_at, but must be excluded from the
    // financial report entirely (only real grants count).
    AidFactory::new()->rejected()->create([
        'beneficiary_id' => Beneficiary::factory()->create()->id,
        'aid_program_id' => $program->id,
        'type' => AidType::Cash,
        'amount' => 9999,
        'decided_at' => '2026-06-10',
    ]);

    $rows = (new FinancialReport(new FinancialFilter))->rows();

    $june = $rows->firstWhere('month', '2026-06');
    $july = $rows->firstWhere('month', '2026-07');

    expect($june)->not->toBeNull();
    expect($june['count'])->toBe(2);
    expect($june['cash_total'])->toBe(1500.0);

    expect($july)->not->toBeNull();
    expect($july['count'])->toBe(1);
    expect($july['cash_total'])->toBe(2000.0);

    $totals = (new FinancialReport(new FinancialFilter))->totals();
    expect($totals['count'])->toBe(3);
    expect($totals['cash_total'])->toBe(3500.0);
});

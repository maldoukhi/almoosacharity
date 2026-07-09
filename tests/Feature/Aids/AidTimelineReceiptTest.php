<?php

use App\Enums\AidProgramType;
use App\Enums\AidType;
use App\Enums\ApprovalAction;
use App\Livewire\Aids\Show;
use App\Models\AidProgram;
use App\Models\ApprovalDecision;
use App\Models\ApprovalFlow;
use App\Models\Beneficiary;
use Database\Factories\AidFactory;
use Livewire\Livewire;

it('includes the creation event and each approval decision in the aid timeline, sorted ascending', function () {
    seedAidCatalog();
    asManager();

    $flow = ApprovalFlow::query()->default()->where('is_active', true)->firstOrFail();
    $stage = $flow->stages()->where('order', 1)->firstOrFail();
    $program = AidProgram::query()->where('type', AidProgramType::Cash)->firstOrFail();

    $aid = AidFactory::new()->underReview()->create([
        'reference' => 'AID-TIMELINE-000001',
        'beneficiary_id' => Beneficiary::factory()->create()->id,
        'aid_program_id' => $program->id,
        'type' => AidType::Cash,
        'amount' => 800,
        'approval_flow_id' => $flow->id,
        'current_stage_id' => $stage->id,
    ]);

    $decision = ApprovalDecision::create([
        'aid_id' => $aid->id,
        'approval_flow_stage_id' => $stage->id,
        'stage_name' => $stage->name,
        'user_id' => auth()->id(),
        'action' => ApprovalAction::Approve,
        'note' => 'موافقة أولية',
        'decided_at' => now(),
    ]);

    $timeline = collect(
        Livewire::test(Show::class, ['aid' => $aid])
            ->instance()
            ->timeline
    );

    // The creation event is always present, carrying the creator's name.
    $createdEntry = $timeline->firstWhere('title', __('aids.timeline.created'));
    expect($createdEntry)->not->toBeNull();
    expect($createdEntry['meta'])->toBe($aid->createdBy?->name);

    // The recorded decision shows up too, with its stage/actor/note.
    $decisionEntry = $timeline->first(
        fn (array $entry): bool => str_contains($entry['title'], $decision->action->label())
            && str_contains($entry['title'], $stage->name)
    );
    expect($decisionEntry)->not->toBeNull();
    expect($decisionEntry['meta'])->toContain('موافقة أولية');
    expect($decisionEntry['color'])->toBe('approved');

    // The whole timeline is sorted ascending (oldest first).
    $timestamps = $timeline->pluck('at')->map(fn ($at) => $at->getTimestamp())->values()->all();
    expect($timestamps)->toBe(collect($timestamps)->sort()->values()->all());
});

it('defensively skips a disbursement/confirmation-free aid without erroring', function () {
    seedAidCatalog();
    asManager();

    $program = AidProgram::query()->where('type', AidProgramType::Cash)->firstOrFail();

    $aid = AidFactory::new()->draft()->create([
        'reference' => 'AID-TIMELINE-000002',
        'beneficiary_id' => Beneficiary::factory()->create()->id,
        'aid_program_id' => $program->id,
        'type' => AidType::Cash,
        'amount' => 400,
    ]);

    $timeline = collect(
        Livewire::test(Show::class, ['aid' => $aid])
            ->instance()
            ->timeline
    );

    // Only the creation event exists for a bare draft aid.
    expect($timeline)->toHaveCount(1);
    expect($timeline->first()['title'])->toBe(__('aids.timeline.created'));
});

it('lets an authorized user download the PDF receipt once the aid is approved', function () {
    seedAidCatalog();
    asManager();

    $program = AidProgram::query()->where('type', AidProgramType::Cash)->firstOrFail();

    $aid = AidFactory::new()->approved()->create([
        'reference' => 'AID-RECEIPT-000001',
        'beneficiary_id' => Beneficiary::factory()->create()->id,
        'aid_program_id' => $program->id,
        'type' => AidType::Cash,
        'amount' => 1200,
    ]);

    Livewire::test(Show::class, ['aid' => $aid])
        ->assertSet('aid.id', $aid->id)
        ->call('downloadReceipt')
        ->assertFileDownloaded('receipt-'.$aid->reference.'.pdf');
});

it('only offers the receipt download once the aid has reached approved-or-later, never on a draft', function () {
    seedAidCatalog();
    asManager();

    $program = AidProgram::query()->where('type', AidProgramType::Cash)->firstOrFail();

    $draftAid = AidFactory::new()->draft()->create([
        'reference' => 'AID-RECEIPT-000002',
        'beneficiary_id' => Beneficiary::factory()->create()->id,
        'aid_program_id' => $program->id,
        'type' => AidType::Cash,
        'amount' => 300,
    ]);

    $approvedAid = AidFactory::new()->approved()->create([
        'reference' => 'AID-RECEIPT-000003',
        'beneficiary_id' => Beneficiary::factory()->create()->id,
        'aid_program_id' => $program->id,
        'type' => AidType::Cash,
        'amount' => 500,
    ]);

    expect(Livewire::test(Show::class, ['aid' => $draftAid])->instance()->canDownloadReceipt)->toBeFalse();
    expect(Livewire::test(Show::class, ['aid' => $approvedAid])->instance()->canDownloadReceipt)->toBeTrue();
});

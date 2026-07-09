<?php

use App\Actions\Aids\GenerateRecurringAids;
use App\Enums\AidProgramType;
use App\Enums\AidType;
use App\Enums\RecurrenceFrequency;
use App\Livewire\Aids\RecurringPlans\Index;
use App\Models\Aid;
use App\Models\AidProgram;
use App\Models\Beneficiary;
use App\Models\RecurringAidPlan;
use Carbon\CarbonImmutable;
use Database\Factories\AidFactory;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

/**
 * A draft cash source aid the recurrence generator can clone (kept distinct
 * from RecurringAidTest's helper to avoid a redeclare).
 */
function seriesSourceAid(int $creatorId, ?Beneficiary $beneficiary = null, ?AidProgram $program = null): Aid
{
    $program ??= AidProgram::query()->where('type', AidProgramType::Cash)->firstOrFail();
    $beneficiary ??= Beneficiary::factory()->create();

    return AidFactory::new()->draft()->create([
        'reference' => 'AID-SRC-'.uniqid(),
        'beneficiary_id' => $beneficiary->id,
        'aid_program_id' => $program->id,
        'type' => AidType::Cash,
        'amount' => 900,
        'purpose' => 'إعانة دورية',
        'created_by' => $creatorId,
    ]);
}

function makeSeriesPlan(Aid $source, array $attrs = []): RecurringAidPlan
{
    return RecurringAidPlan::create(array_merge([
        'aid_id' => $source->id,
        'frequency' => RecurrenceFrequency::Monthly,
        'starts_on' => CarbonImmutable::parse('2026-07-01'),
        'next_run_on' => CarbonImmutable::parse('2026-07-20'),
        'lead_days' => 0,
        'is_active' => true,
    ], $attrs));
}

it('generates a cycle exactly lead_days before the due date, and not sooner', function () {
    seedAidCatalog();
    $actor = asDataEntry();

    $source = seriesSourceAid($actor->id);
    $plan = makeSeriesPlan($source, [
        'next_run_on' => CarbonImmutable::parse('2026-07-20'),
        'lead_days' => 5,
    ]);

    // One day before the lead window (due - 5 = Jul 15) opens: nothing.
    $before = app(GenerateRecurringAids::class)->handle(CarbonImmutable::parse('2026-07-14'));

    expect($before)->toBe(0)
        ->and($plan->fresh()->aids()->count())->toBe(0);

    // On the lead window: the cycle fires.
    $on = app(GenerateRecurringAids::class)->handle(CarbonImmutable::parse('2026-07-15'));

    expect($on)->toBe(1)
        ->and($plan->fresh()->aids()->count())->toBe(1);
});

it('generates nothing for a paused plan even when it is due', function () {
    seedAidCatalog();
    $actor = asDataEntry();

    $source = seriesSourceAid($actor->id);
    $plan = makeSeriesPlan($source, [
        'next_run_on' => CarbonImmutable::parse('2026-07-01'),
        'is_active' => false,
    ]);

    $generated = app(GenerateRecurringAids::class)->handle(CarbonImmutable::parse('2026-07-20'));

    expect($generated)->toBe(0)
        ->and($plan->fresh()->aids()->count())->toBe(0);
});

it('links each generated aid into the plan series', function () {
    seedAidCatalog();
    $actor = asDataEntry();

    $source = seriesSourceAid($actor->id);
    $today = CarbonImmutable::parse('2026-07-20');
    $plan = makeSeriesPlan($source, ['next_run_on' => $today]);

    app(GenerateRecurringAids::class)->handle($today);

    $clone = Aid::query()->where('id', '!=', $source->id)->latest('id')->firstOrFail();

    expect($clone->recurring_aid_plan_id)->toBe($plan->id)
        ->and($clone->recurringPlanSeries->is($plan))->toBeTrue()
        ->and($plan->aids()->pluck('id')->all())->toContain($clone->id)
        ->and($plan->aids()->count())->toBe(1);
});

it('lists recurring plans on the central management page', function () {
    seedAidCatalog();
    $actor = asAdmin();

    $source = seriesSourceAid($actor->id);
    makeSeriesPlan($source);

    Livewire::test(Index::class)
        ->assertOk()
        ->assertSee($source->beneficiary->full_name)
        ->assertSee($source->program->name);
});

it('pauses and resumes a plan and logs both events', function () {
    seedAidCatalog();
    $actor = asAdmin();

    $source = seriesSourceAid($actor->id);
    $plan = makeSeriesPlan($source, ['is_active' => true]);

    Livewire::test(Index::class)
        ->call('togglePause', $plan->id)
        ->assertDispatched('toast');

    expect($plan->fresh()->is_active)->toBeFalse();

    Livewire::test(Index::class)->call('togglePause', $plan->id);

    expect($plan->fresh()->is_active)->toBeTrue()
        ->and(Activity::query()->where('description', 'recurring_aid_plan.paused')->exists())->toBeTrue()
        ->and(Activity::query()->where('description', 'recurring_aid_plan.resumed')->exists())->toBeTrue();
});

it('edits a plan schedule from the management page', function () {
    seedAidCatalog();
    $actor = asAdmin();

    $source = seriesSourceAid($actor->id);
    $plan = makeSeriesPlan($source, ['frequency' => RecurrenceFrequency::Monthly, 'lead_days' => 0]);

    Livewire::test(Index::class)
        ->call('openEdit', $plan->id)
        ->assertSet('editingPlanId', $plan->id)
        ->set('editFrequency', RecurrenceFrequency::Quarterly->value)
        ->set('editLeadDays', 7)
        ->call('saveEdit')
        ->assertHasNoErrors()
        ->assertSet('editingPlanId', null);

    $plan->refresh();

    expect($plan->frequency)->toBe(RecurrenceFrequency::Quarterly)
        ->and($plan->lead_days)->toBe(7);
});

it('deletes a plan but keeps the aids it already generated', function () {
    seedAidCatalog();
    $actor = asAdmin();

    $source = seriesSourceAid($actor->id);
    $today = CarbonImmutable::parse('2026-07-20');
    $plan = makeSeriesPlan($source, ['next_run_on' => $today]);

    app(GenerateRecurringAids::class)->handle($today);
    $clone = Aid::query()->where('recurring_aid_plan_id', $plan->id)->firstOrFail();

    Livewire::test(Index::class)
        ->call('delete', $plan->id)
        ->assertDispatched('toast');

    expect(RecurringAidPlan::query()->find($plan->id))->toBeNull()
        ->and($clone->fresh()->recurring_aid_plan_id)->toBeNull()
        ->and(Activity::query()->where('description', 'recurring_aid_plan.deleted')->exists())->toBeTrue();
});

it('forbids managing plans without the aids.update / aids.delete permissions', function () {
    seedAidCatalog();
    $actor = asDataEntry(); // holds aids.view, but not aids.update or aids.delete

    $source = seriesSourceAid($actor->id);
    $plan = makeSeriesPlan($source);

    Livewire::test(Index::class)->call('togglePause', $plan->id)->assertForbidden();
    expect($plan->fresh()->is_active)->toBeTrue();

    Livewire::test(Index::class)->call('openEdit', $plan->id)->assertForbidden();

    Livewire::test(Index::class)->call('delete', $plan->id)->assertForbidden();
    expect(RecurringAidPlan::query()->find($plan->id))->not->toBeNull();
});

it('scopes the list to a single beneficiary in embedded mode', function () {
    seedAidCatalog();
    $actor = asAdmin();

    $progA = AidProgram::query()->where('name', 'إعانة نقدية عامة')->firstOrFail();
    $progB = AidProgram::query()->where('name', 'سداد إيجار')->firstOrFail();

    $beneA = Beneficiary::factory()->create();
    $beneB = Beneficiary::factory()->create();

    makeSeriesPlan(seriesSourceAid($actor->id, $beneA, $progA));
    makeSeriesPlan(seriesSourceAid($actor->id, $beneB, $progB));

    Livewire::test(Index::class)
        ->set('beneficiaryId', $beneA->id)
        ->set('embedded', true)
        ->assertOk()
        ->assertSee('إعانة نقدية عامة')
        ->assertDontSee('سداد إيجار');
});

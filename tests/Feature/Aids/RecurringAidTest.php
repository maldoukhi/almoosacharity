<?php

use App\Actions\Aids\GenerateRecurringAids;
use App\Enums\AidProgramType;
use App\Enums\AidStatus;
use App\Enums\AidType;
use App\Enums\RecurrenceFrequency;
use App\Livewire\Aids\Form;
use App\Models\Aid;
use App\Models\AidProgram;
use App\Models\Beneficiary;
use App\Models\RecurringAidPlan;
use Carbon\CarbonImmutable;
use Database\Factories\AidFactory;
use Livewire\Livewire;

/**
 * Create a draft cash source aid owned by the current actor, ready to be
 * cloned by a recurrence plan.
 */
function recurringSourceAid(int $creatorId): Aid
{
    $program = AidProgram::query()->where('type', AidProgramType::Cash)->firstOrFail();

    return AidFactory::new()->draft()->create([
        // A reference outside the 'AID-{year}-{seq}' space CreateAid derives
        // from max(id): keeps the factory's own static sequence from
        // colliding with the id-based references the generator's clones get.
        'reference' => 'AID-SRC-'.uniqid(),
        'beneficiary_id' => Beneficiary::factory()->create()->id,
        'aid_program_id' => $program->id,
        'type' => AidType::Cash,
        'amount' => 1200,
        'purpose' => 'إعانة شهرية',
        'created_by' => $creatorId,
    ]);
}

it('persists a recurring plan when an aid is created with recurrence enabled', function () {
    seedAidCatalog();
    $actor = asDataEntry();

    $program = AidProgram::query()->where('type', AidProgramType::Cash)->firstOrFail();
    $beneficiary = Beneficiary::factory()->create();
    $startsOn = CarbonImmutable::now()->startOfDay();

    Livewire::test(Form::class)
        ->set('beneficiary_ids', [$beneficiary->id])
        ->set('aid_program_id', $program->id)
        ->set('type', AidType::Cash->value)
        ->set('amount', 750)
        ->set('purpose', 'سلة غذائية')
        ->set('isRecurring', true)
        ->set('recurrenceFrequency', RecurrenceFrequency::Monthly->value)
        ->set('recurrenceStartsOn', $startsOn->toDateString())
        ->call('save')
        ->assertHasNoErrors();

    $aid = Aid::query()->where('created_by', $actor->id)->latest('id')->firstOrFail();
    $plan = $aid->recurringPlan;

    expect($plan)->not->toBeNull()
        ->and($plan->frequency)->toBe(RecurrenceFrequency::Monthly)
        ->and($plan->is_active)->toBeTrue()
        ->and($plan->starts_on->toDateString())->toBe($startsOn->toDateString())
        ->and($plan->next_run_on->toDateString())->toBe($startsOn->toDateString());
});

it('stores the custom interval for a custom-months recurrence', function () {
    seedAidCatalog();
    asDataEntry();

    $program = AidProgram::query()->where('type', AidProgramType::Cash)->firstOrFail();
    $beneficiary = Beneficiary::factory()->create();

    Livewire::test(Form::class)
        ->set('beneficiary_ids', [$beneficiary->id])
        ->set('aid_program_id', $program->id)
        ->set('type', AidType::Cash->value)
        ->set('amount', 500)
        ->set('purpose', 'دعم')
        ->set('isRecurring', true)
        ->set('recurrenceFrequency', RecurrenceFrequency::CustomMonths->value)
        ->set('recurrenceIntervalMonths', 4)
        ->set('recurrenceStartsOn', CarbonImmutable::now()->toDateString())
        ->call('save')
        ->assertHasNoErrors();

    $plan = RecurringAidPlan::query()->latest('id')->firstOrFail();

    expect($plan->frequency)->toBe(RecurrenceFrequency::CustomMonths)
        ->and($plan->interval_months)->toBe(4);
});

it('requires an interval when the frequency is custom months', function () {
    seedAidCatalog();
    asDataEntry();

    $program = AidProgram::query()->where('type', AidProgramType::Cash)->firstOrFail();
    $beneficiary = Beneficiary::factory()->create();

    Livewire::test(Form::class)
        ->set('beneficiary_ids', [$beneficiary->id])
        ->set('aid_program_id', $program->id)
        ->set('type', AidType::Cash->value)
        ->set('amount', 500)
        ->set('purpose', 'دعم')
        ->set('isRecurring', true)
        ->set('recurrenceFrequency', RecurrenceFrequency::CustomMonths->value)
        ->set('recurrenceIntervalMonths', null)
        ->set('recurrenceStartsOn', CarbonImmutable::now()->toDateString())
        ->call('save')
        ->assertHasErrors('recurrenceIntervalMonths');
});

it('clones a due plan into a new draft aid and advances next_run_on', function () {
    seedAidCatalog();
    $actor = asDataEntry();

    $source = recurringSourceAid($actor->id);
    $today = CarbonImmutable::parse('2026-07-09')->startOfDay();

    $plan = RecurringAidPlan::create([
        'aid_id' => $source->id,
        'frequency' => RecurrenceFrequency::Monthly,
        'starts_on' => $today,
        'next_run_on' => $today,
        'is_active' => true,
    ]);

    $generated = app(GenerateRecurringAids::class)->handle($today);

    expect($generated)->toBe(1);

    $clone = Aid::query()->where('id', '!=', $source->id)->latest('id')->firstOrFail();

    expect($clone->beneficiary_id)->toBe($source->beneficiary_id)
        ->and($clone->aid_program_id)->toBe($source->aid_program_id)
        ->and($clone->type)->toBe(AidType::Cash)
        ->and($clone->amount)->toEqual($source->amount)
        ->and($clone->status)->toBe(AidStatus::Draft);

    expect($plan->fresh()->next_run_on->toDateString())->toBe($today->addMonthNoOverflow()->toDateString());
});

it('does not double-generate for the same day', function () {
    seedAidCatalog();
    $actor = asDataEntry();

    $source = recurringSourceAid($actor->id);
    $today = CarbonImmutable::parse('2026-07-09')->startOfDay();

    RecurringAidPlan::create([
        'aid_id' => $source->id,
        'frequency' => RecurrenceFrequency::Monthly,
        'starts_on' => $today,
        'next_run_on' => $today,
        'is_active' => true,
    ]);

    app(GenerateRecurringAids::class)->handle($today);
    $secondRun = app(GenerateRecurringAids::class)->handle($today);

    expect($secondRun)->toBe(0)
        ->and(Aid::query()->count())->toBe(2); // source + one clone only
});

it('stops generating and deactivates a plan past its end date', function () {
    seedAidCatalog();
    $actor = asDataEntry();

    $source = recurringSourceAid($actor->id);
    $today = CarbonImmutable::parse('2026-07-09')->startOfDay();

    $plan = RecurringAidPlan::create([
        'aid_id' => $source->id,
        'frequency' => RecurrenceFrequency::Monthly,
        'starts_on' => $today->subMonths(3),
        'ends_on' => $today->subMonth(),
        'next_run_on' => $today->subMonth(),
        'is_active' => true,
    ]);

    $generated = app(GenerateRecurringAids::class)->handle($today);

    expect($generated)->toBe(0)
        ->and($plan->fresh()->is_active)->toBeFalse()
        ->and(Aid::query()->count())->toBe(1); // no clone produced
});

it('deactivates a plan once the newly advanced run falls past ends_on', function () {
    seedAidCatalog();
    $actor = asDataEntry();

    $source = recurringSourceAid($actor->id);
    $today = CarbonImmutable::parse('2026-07-09')->startOfDay();

    $plan = RecurringAidPlan::create([
        'aid_id' => $source->id,
        'frequency' => RecurrenceFrequency::Monthly,
        'starts_on' => $today,
        // Ends before the next monthly cycle would fire.
        'ends_on' => $today->addDays(10),
        'next_run_on' => $today,
        'is_active' => true,
    ]);

    $generated = app(GenerateRecurringAids::class)->handle($today);

    expect($generated)->toBe(1)
        ->and($plan->fresh()->is_active)->toBeFalse();
});

<?php

use App\Actions\Beneficiaries\CreateBeneficiary;
use App\Actions\Beneficiaries\RevealBankData;
use App\Actions\Beneficiaries\UpdateBeneficiary;
use App\Enums\UserStatus;
use App\Livewire\Beneficiaries\Profile\ActivityDetailModal;
use App\Livewire\Beneficiaries\Profile\ActivityLog;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

it('renders a compact activity row with a translated event title, actor, and a details button', function () {
    $actor = asDataEntry();

    $beneficiary = app(CreateBeneficiary::class)->handle(
        beneficiaryAttributes(['marital_status' => 'married']),
        [],
        $actor,
    );

    Livewire::test(ActivityLog::class, ['beneficiary' => $beneficiary])
        ->assertSee(__('beneficiaries.activity.event.created'))
        ->assertSee(__('beneficiaries.activity.details_button'))
        // The compact row must not leak raw enum values or bank data.
        ->assertDontSee('married')
        ->assertDontSee('SA1234567890123456789012');
});

it('shows only non-empty fields, with translated enum/nationality values, for a created activity', function () {
    $actor = asDataEntry();

    $beneficiary = app(CreateBeneficiary::class)->handle(
        beneficiaryAttributes([
            'marital_status' => 'married',
            'gender' => 'male',
            'nationality' => 'SA',
            'third_name' => '',
            'special_needs' => '',
        ]),
        [],
        $actor,
    );

    $activity = Activity::query()
        ->where('subject_type', $beneficiary->getMorphClass())
        ->where('subject_id', $beneficiary->id)
        ->where('event', 'created')
        ->latest('id')
        ->first();

    expect($activity)->not->toBeNull();

    $component = Livewire::test(ActivityDetailModal::class, [
        'beneficiary' => $beneficiary,
        'activityId' => $activity->id,
    ]);

    $changes = collect($component->instance()->changes())->keyBy('field');

    $maritalStatus = $changes->get(__('beneficiaries.field_marital_status'));
    expect($maritalStatus)->not->toBeNull();
    expect($maritalStatus['old'])->toBeNull();
    expect($maritalStatus['new'])->toBe(__('beneficiaries.marital_status.married'));
    expect($maritalStatus['new'])->not->toBe('married');

    $nationality = $changes->get(__('beneficiaries.field_nationality'));
    expect($nationality['new'])->not->toBe('SA');

    // Blank fields on creation carry nothing meaningful and must not
    // appear in the diff.
    expect($changes->has(__('beneficiaries.field_third_name')))->toBeFalse();
    expect($changes->has(__('beneficiaries.field_special_needs')))->toBeFalse();

    $component->assertSee(__('beneficiaries.activity.changes_title'))
        ->assertDontSee('married');
});

it('shows only the field that actually changed for an updated activity, formatted "old -> new"', function () {
    $actor = asDataEntry();

    $beneficiary = app(CreateBeneficiary::class)->handle(
        beneficiaryAttributes(['city' => 'الرياض', 'marital_status' => 'married']),
        [],
        $actor,
    );

    app(UpdateBeneficiary::class)->handle(
        $beneficiary,
        array_merge(beneficiaryAttributes(['marital_status' => 'married']), ['city' => 'جدة']),
        [],
        $actor,
    );

    $activity = Activity::query()
        ->where('subject_type', $beneficiary->getMorphClass())
        ->where('subject_id', $beneficiary->id)
        ->where('event', 'updated')
        ->latest('id')
        ->first();

    expect($activity)->not->toBeNull();

    $changes = collect(
        Livewire::test(ActivityDetailModal::class, ['beneficiary' => $beneficiary, 'activityId' => $activity->id])
            ->instance()
            ->changes(),
    )->keyBy('field');

    $city = $changes->get(__('beneficiaries.field_city'));
    expect($city['old'])->toBe('الرياض');
    expect($city['new'])->toBe('جدة');

    // marital_status was resubmitted unchanged, so it must not appear
    // even though it was present in the update payload.
    expect($changes->has(__('beneficiaries.field_marital_status')))->toBeFalse();
});

it('shows a simple no-changes summary in the detail modal for a bank-data-reveal activity', function () {
    seedRolesAndPermissions();
    $role = Role::findOrCreate('activity-bank-viewer', 'web');
    $role->syncPermissions(['beneficiaries.view', 'beneficiaries.bank-data.view']);

    $creator = asDataEntry();
    $beneficiary = app(CreateBeneficiary::class)->handle(beneficiaryAttributes(), [], $creator);

    $viewer = User::factory()->create(['status' => UserStatus::Active]);
    $viewer->assignRole('activity-bank-viewer');

    app(RevealBankData::class)->handle($beneficiary, $viewer);

    $activity = Activity::query()
        ->where('log_name', 'bank-data-reveal')
        ->where('subject_id', $beneficiary->id)
        ->latest('id')
        ->first();

    expect($activity)->not->toBeNull();

    test()->actingAs($viewer);

    Livewire::test(ActivityDetailModal::class, ['beneficiary' => $beneficiary, 'activityId' => $activity->id])
        ->assertSee(__('beneficiaries.activity.event.bank_data_revealed'))
        ->assertSee(__('beneficiaries.activity.no_changes'))
        ->assertDontSee(__('beneficiaries.activity.changes_title'));
});

it('refuses to show an activity that belongs to a different beneficiary', function () {
    $actor = asDataEntry();

    $beneficiaryA = app(CreateBeneficiary::class)->handle(beneficiaryAttributes(), [], $actor);
    $beneficiaryB = app(CreateBeneficiary::class)->handle(beneficiaryAttributes(), [], $actor);

    $activityForA = Activity::query()
        ->where('subject_type', $beneficiaryA->getMorphClass())
        ->where('subject_id', $beneficiaryA->id)
        ->latest('id')
        ->first();

    expect($activityForA)->not->toBeNull();

    Livewire::test(ActivityDetailModal::class, [
        'beneficiary' => $beneficiaryB,
        'activityId' => $activityForA->id,
    ])->assertStatus(404);
});

it('requires beneficiaries.view to open the activity detail modal', function () {
    $actor = asDataEntry();
    $beneficiary = app(CreateBeneficiary::class)->handle(beneficiaryAttributes(), [], $actor);

    $activity = Activity::query()
        ->where('subject_type', $beneficiary->getMorphClass())
        ->where('subject_id', $beneficiary->id)
        ->latest('id')
        ->first();

    // Log in as a user with no roles/permissions at all.
    seedRolesAndPermissions();
    $stranger = User::factory()->create(['status' => UserStatus::Active]);
    test()->actingAs($stranger);

    Livewire::test(ActivityDetailModal::class, [
        'beneficiary' => $beneficiary,
        'activityId' => $activity->id,
    ])->assertForbidden();
});

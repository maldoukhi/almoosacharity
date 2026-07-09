<?php

use App\Enums\AidProgramType;
use App\Enums\AidType;
use App\Enums\BeneficiaryStatus;
use App\Enums\RecurrenceFrequency;
use App\Livewire\Dashboard;
use App\Models\Aid;
use App\Models\AidConfirmation;
use App\Models\AidProgram;
use App\Models\Beneficiary;
use App\Models\RecurringAidPlan;
use App\Models\User;
use Database\Factories\AidFactory;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

it('counts overdue approvals and expired confirmations in the operational indicators', function () {
    asAdmin();

    seedAidCatalog();
    $program = AidProgram::query()->where('type', AidProgramType::Cash)->firstOrFail();

    // Overdue: under review, submitted well over 7 days ago.
    AidFactory::new()->underReview()->create([
        'beneficiary_id' => Beneficiary::factory()->create()->id,
        'aid_program_id' => $program->id,
        'type' => AidType::Cash,
        'amount' => 100,
        'submitted_at' => now()->subDays(10),
    ]);

    // Not overdue yet: under review but submitted recently.
    AidFactory::new()->underReview()->create([
        'beneficiary_id' => Beneficiary::factory()->create()->id,
        'aid_program_id' => $program->id,
        'type' => AidType::Cash,
        'amount' => 100,
        'submitted_at' => now()->subDays(2),
    ]);

    // Expired confirmation on a still-Delivered aid: should count.
    $deliveredAid = AidFactory::new()->delivered()->create([
        'beneficiary_id' => Beneficiary::factory()->create()->id,
        'aid_program_id' => $program->id,
        'type' => AidType::Cash,
        'amount' => 100,
    ]);
    AidConfirmation::factory()->expired()->create(['aid_id' => $deliveredAid->id]);

    // Confirmed (not expired-without-response): should not count.
    $confirmedAid = AidFactory::new()->delivered()->create([
        'beneficiary_id' => Beneficiary::factory()->create()->id,
        'aid_program_id' => $program->id,
        'type' => AidType::Cash,
        'amount' => 100,
    ]);
    AidConfirmation::factory()->confirmed()->create(['aid_id' => $confirmedAid->id]);

    $dashboard = Livewire::test(Dashboard::class)->instance();

    expect($dashboard->overdueApprovalsCount)->toBe(1);
    expect($dashboard->expiredConfirmationsCount)->toBe(1);
});

it('counts recurring plans due soon and overdue beneficiary reviews', function () {
    asAdmin();

    seedAidCatalog();
    $program = AidProgram::query()->where('type', AidProgramType::Cash)->firstOrFail();

    $sourceAid = AidFactory::new()->draft()->create([
        'beneficiary_id' => Beneficiary::factory()->create()->id,
        'aid_program_id' => $program->id,
        'type' => AidType::Cash,
        'amount' => 100,
    ]);

    // Due soon: active, next run within the 7-day window.
    RecurringAidPlan::query()->create([
        'aid_id' => $sourceAid->id,
        'frequency' => RecurrenceFrequency::Monthly,
        'starts_on' => now()->subMonth(),
        'next_run_on' => now()->addDays(3),
        'is_active' => true,
    ]);

    // Not due soon: active, but next run far in the future.
    RecurringAidPlan::query()->create([
        'aid_id' => $sourceAid->id,
        'frequency' => RecurrenceFrequency::Monthly,
        'starts_on' => now()->subMonth(),
        'next_run_on' => now()->addDays(30),
        'is_active' => true,
    ]);

    // Due soon but inactive: should not count.
    RecurringAidPlan::query()->create([
        'aid_id' => $sourceAid->id,
        'frequency' => RecurrenceFrequency::Monthly,
        'starts_on' => now()->subMonth(),
        'next_run_on' => now()->addDay(),
        'is_active' => false,
    ]);

    // Overdue beneficiary review: under review, stale for 10 days.
    $stale = Beneficiary::factory()->create(['status' => BeneficiaryStatus::UnderReview]);
    DB::table('beneficiaries')->where('id', $stale->id)->update(['updated_at' => now()->subDays(10)]);

    // Recently touched under-review beneficiary: should not count.
    Beneficiary::factory()->create(['status' => BeneficiaryStatus::UnderReview]);

    // Active beneficiary, stale for 10 days but wrong status: should not count.
    $activeStale = Beneficiary::factory()->create(['status' => BeneficiaryStatus::Active]);
    DB::table('beneficiaries')->where('id', $activeStale->id)->update(['updated_at' => now()->subDays(10)]);

    $dashboard = Livewire::test(Dashboard::class);

    expect($dashboard->instance()->upcomingRecurringPlansCount)->toBe(1);
    expect($dashboard->instance()->overdueBeneficiaryReviewsCount)->toBe(1);

    $dashboard
        ->assertSee(__('dashboard.ops.title'))
        ->assertSee(__('dashboard.ops.overdue_approvals_label'))
        ->assertSee(__('dashboard.ops.expired_confirmations_label'))
        ->assertSee(__('dashboard.ops.upcoming_recurring_label'))
        ->assertSee(__('dashboard.ops.overdue_beneficiary_reviews_label'));
});

it('hides operational indicator cards a role lacks the matching permission for', function () {
    asDataEntry();

    seedAidCatalog();

    Livewire::test(Dashboard::class)
        ->assertDontSee(__('dashboard.ops.overdue_approvals_label'));
});

it('hides every stat, chart and receipt-issue figure from a user with no view permissions', function () {
    // A bare authenticated user with no role: the dashboard must not leak
    // beneficiary/aid counts, monthly cash sums, or beneficiary names.
    $user = User::factory()->create();
    test()->actingAs($user);

    $dashboard = Livewire::test(Dashboard::class);

    $dashboard
        ->assertDontSee(__('ui.stat_beneficiaries'))
        ->assertDontSee(__('ui.stat_aids'))
        ->assertDontSee(__('reports.dashboard.receipt_issues_title'))
        ->assertDontSee(__('reports.dashboard.chart_by_month'));

    // Defense-in-depth: the computeds themselves return nothing.
    expect($dashboard->instance()->beneficiariesCount)->toBe(0);
    expect($dashboard->instance()->aidsCount)->toBe(0);
    expect($dashboard->instance()->receiptIssues)->toBeEmpty();
    expect($dashboard->instance()->aidsByMonth['cashSums'])->toBe([]);
});

<?php

use App\Actions\Aids\SubmitAid;
use App\Enums\AidProgramType;
use App\Enums\AidStatus;
use App\Enums\AidType;
use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\AidProgram;
use App\Models\Beneficiary;
use App\Notifications\AidAwaitingReviewNotification;
use Database\Factories\AidFactory;

it('notifies only active users holding the entered stage role when an aid is submitted', function () {
    $creator = asDataEntry();

    $activeResearcher = userWithRole(RoleName::SocialResearcher);
    $suspendedResearcher = userWithRole(RoleName::SocialResearcher, ['status' => UserStatus::Suspended]);
    $unrelatedManager = userWithRole(RoleName::Manager);

    seedAidCatalog();
    $program = AidProgram::query()->where('type', AidProgramType::Cash)->firstOrFail();
    $beneficiary = Beneficiary::factory()->create();

    $aid = AidFactory::new()->draft()->create([
        'beneficiary_id' => $beneficiary->id,
        'aid_program_id' => $program->id,
        'type' => AidType::Cash,
        'amount' => 1100,
        'created_by' => $creator->id,
    ]);

    app(SubmitAid::class)->handle($aid, $creator);

    expect($aid->fresh()->status)->toBe(AidStatus::UnderReview);

    expect($activeResearcher->notifications()->count())->toBe(1);
    expect($activeResearcher->notifications()->first()->type)->toBe(AidAwaitingReviewNotification::class);

    expect($suspendedResearcher->notifications()->count())->toBe(0);
    expect($unrelatedManager->notifications()->count())->toBe(0);
});

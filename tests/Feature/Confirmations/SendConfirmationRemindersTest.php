<?php

use App\Actions\Confirmations\ResendConfirmationLink;
use App\Enums\AidProgramType;
use App\Enums\AidStatus;
use App\Enums\AidType;
use App\Jobs\Confirmations\SendConfirmationReminders;
use App\Models\AidProgram;
use App\Models\Beneficiary;
use App\Models\MessageLog;
use Database\Factories\AidConfirmationFactory;
use Database\Factories\AidFactory;

function outstandingUnconfirmedConfirmation(int $sentDaysAgo = 4)
{
    seedAidCatalog();

    // BeneficiaryFactory::definition() resolves `created_by` from an
    // existing data-entry/system-admin user, so one must exist first.
    userWithRole(\App\Enums\RoleName::DataEntry);

    $program = AidProgram::query()->where('type', AidProgramType::Cash)->firstOrFail();
    $beneficiary = Beneficiary::factory()->create(['mobile' => '0509998888']);

    $aid = AidFactory::new()->approved()->create([
        'beneficiary_id' => $beneficiary->id,
        'aid_program_id' => $program->id,
        'type' => AidType::Cash,
        'amount' => 350,
        'status' => AidStatus::Delivered,
    ]);

    return AidConfirmationFactory::new()->create([
        'aid_id' => $aid->id,
        'sent_at' => now()->subDays($sentDaysAgo),
        'expires_at' => now()->addDays(7 - $sentDaysAgo),
        'reminder_sent_at' => null,
        'confirmed_at' => null,
    ]);
}

it('sends exactly one reminder for a confirmation outstanding past the 3-day threshold', function () {
    $confirmation = outstandingUnconfirmedConfirmation(sentDaysAgo: 4);

    expect($confirmation->reminder_sent_at)->toBeNull();

    app(SendConfirmationReminders::class)->handle(app(ResendConfirmationLink::class));

    $confirmation->refresh();
    expect($confirmation->reminder_sent_at)->not->toBeNull();
    expect(MessageLog::query()->where('recipient', '0509998888')->count())->toBe(1);
});

it('does not send a second reminder for the same confirmation on a later run', function () {
    $confirmation = outstandingUnconfirmedConfirmation(sentDaysAgo: 4);

    app(SendConfirmationReminders::class)->handle(app(ResendConfirmationLink::class));
    $firstReminderAt = $confirmation->fresh()->reminder_sent_at;

    // Run the sweep again straight away.
    app(SendConfirmationReminders::class)->handle(app(ResendConfirmationLink::class));

    $confirmation->refresh();
    expect($confirmation->reminder_sent_at->equalTo($firstReminderAt))->toBeTrue();
    expect(MessageLog::query()->where('recipient', '0509998888')->count())->toBe(1);
});

it('does not remind a confirmation that has not yet reached the 3-day threshold', function () {
    $confirmation = outstandingUnconfirmedConfirmation(sentDaysAgo: 1);

    app(SendConfirmationReminders::class)->handle(app(ResendConfirmationLink::class));

    expect($confirmation->fresh()->reminder_sent_at)->toBeNull();
});

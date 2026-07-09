<?php

use App\Actions\Aids\SubmitAid;
use App\Enums\AidProgramType;
use App\Enums\AidType;
use App\Enums\MessageChannel;
use App\Enums\NotificationEvent;
use App\Enums\RoleName;
use App\Mail\OutboundMessage;
use App\Models\AidProgram;
use App\Models\ApprovalFlowStage;
use App\Models\Beneficiary;
use App\Models\MessageLog;
use App\Models\NotificationTemplate;
use App\Notifications\AidAwaitingReviewNotification;
use Database\Factories\AidFactory;
use Illuminate\Support\Facades\Mail;

/**
 * Feature 6: an aid entering a stage whose notify_channels include 'email'
 * notifies each assigned approver by email (rendered from the staff
 * AidAwaitingApproval template) in addition to the in-app bell — everything
 * queued and written to message_logs.
 */
function submitAidIntoResearcherStage(): array
{
    $creator = asDataEntry();
    $researcher = userWithRole(RoleName::SocialResearcher, ['email' => 'approver@example.org']);

    seedAidCatalog();

    $program = AidProgram::query()->where('type', AidProgramType::Cash)->firstOrFail();
    $beneficiary = Beneficiary::factory()->create();

    $aid = AidFactory::new()->draft()->create([
        'beneficiary_id' => $beneficiary->id,
        'aid_program_id' => $program->id,
        'type' => AidType::Cash,
        'amount' => 1200,
        'created_by' => $creator->id,
    ]);

    return [$creator, $researcher, $aid];
}

it('emails stage approvers and rings the in-app bell when the stage selects the email channel', function () {
    Mail::fake();

    [$creator, $researcher, $aid] = submitAidIntoResearcherStage();

    // Turn on in-app + email for the first (researcher) stage.
    ApprovalFlowStage::query()
        ->where('role', RoleName::SocialResearcher->value)
        ->firstOrFail()
        ->update(['notify_channels' => ['in_app', 'email']]);

    // Active staff email template.
    NotificationTemplate::query()->updateOrCreate(
        ['event' => NotificationEvent::AidAwaitingApproval->value, 'channel' => MessageChannel::Email->value],
        ['body' => 'إعانة {reference} ({program}) وصلت إلى مرحلة {stage}. راجعها: {link}', 'is_active' => true],
    );

    app(SubmitAid::class)->handle($aid, $creator);

    // In-app bell still fires (exactly one, of the expected type).
    expect($researcher->notifications()->count())->toBe(1)
        ->and($researcher->notifications()->first()->type)->toBe(AidAwaitingReviewNotification::class);

    // Email delivered to the approver and recorded in the audit trail.
    Mail::assertSent(OutboundMessage::class, fn (OutboundMessage $mail): bool => $mail->hasTo('approver@example.org'));

    $log = MessageLog::query()
        ->where('channel', MessageChannel::Email)
        ->where('recipient', 'approver@example.org')
        ->first();

    expect($log)->not->toBeNull();
    expect($log->body)->toContain((string) $aid->fresh()->reference);
});

it('does not email approvers when the stage names no email channel (bell only)', function () {
    Mail::fake();

    [$creator, $researcher, $aid] = submitAidIntoResearcherStage();

    // Seeded stage has no notify_channels → default is the in-app bell only.
    app(SubmitAid::class)->handle($aid, $creator);

    expect($researcher->notifications()->count())->toBe(1);

    Mail::assertNotSent(OutboundMessage::class);
    expect(MessageLog::query()->where('channel', MessageChannel::Email)->count())->toBe(0);
});

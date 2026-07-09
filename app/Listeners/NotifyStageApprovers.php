<?php

namespace App\Listeners;

use App\Enums\MessageChannel;
use App\Enums\NotificationEvent;
use App\Events\Approvals\AidEnteredStage;
use App\Models\Aid;
use App\Models\ApprovalFlowStage;
use App\Models\NotificationTemplate;
use App\Models\User;
use App\Notifications\AidAwaitingReviewNotification;
use App\Services\Messaging\Messenger;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

/**
 * Notifies every active user eligible for an approval stage as soon as an
 * aid becomes current at it, over the channels chosen on that stage
 * (stage->notify_channels): the in-app bell (always the fallback when a
 * stage names no channels), email, and/or WhatsApp. Email/WhatsApp bodies
 * are rendered from the editable {@see NotificationEvent::AidAwaitingApproval}
 * templates; everything is queued and written to message_logs by
 * {@see Messenger}.
 */
class NotifyStageApprovers implements ShouldQueue
{
    public function __construct(private readonly Messenger $messenger) {}

    public function handle(AidEnteredStage $event): void
    {
        // A beneficiary_response stage waits on the beneficiary, not staff:
        // no approval prompt is sent here (the beneficiary link is issued by
        // IssueBeneficiaryStageLink instead).
        if ($event->stage->isBeneficiaryResponse()) {
            return;
        }

        // Everyone eligible for the stage: its role's holders unioned with
        // any specifically-assigned users (both active only).
        $recipients = $event->stage->eligibleUsers();

        if ($recipients->isEmpty()) {
            return;
        }

        $channels = $this->channelsFor($event->stage);

        // In-app bell (database notification). Always the fallback when the
        // stage names no channels, preserving the historical behaviour.
        if (in_array('in_app', $channels, true)) {
            Notification::sendNow(
                $recipients,
                new AidAwaitingReviewNotification($event->aid, $event->stage),
                ['database'],
            );
        }

        $wantsEmail = in_array('email', $channels, true);
        $wantsWhatsapp = in_array('whatsapp', $channels, true);

        if (! $wantsEmail && ! $wantsWhatsapp) {
            return;
        }

        $vars = $this->buildVars($event->aid, $event->stage);
        $emailBody = $wantsEmail ? $this->renderTemplate(MessageChannel::Email, $vars) : null;
        $whatsappBody = $wantsWhatsapp ? $this->renderTemplate(MessageChannel::WhatsApp, $vars) : null;

        foreach ($recipients as $recipient) {
            if ($emailBody !== null && filled($recipient->email)) {
                $this->messenger->email(
                    $recipient->email,
                    __('notifications.mail.awaiting_review.subject'),
                    $emailBody,
                    related: $event->aid,
                );
            }

            if ($whatsappBody !== null && filled($recipient->phone)) {
                $this->messenger->whatsappText(
                    $recipient->phone,
                    $whatsappBody,
                    related: $event->aid,
                    idempotencyKey: "aid-{$event->aid->id}-stage-{$event->stage->id}-approver-{$recipient->id}-wa",
                );
            }
        }
    }

    /**
     * The channels selected on the stage, defaulting to the in-app bell
     * when the stage names none (keeps the always-on bell working).
     *
     * @return array<int, string>
     */
    private function channelsFor(ApprovalFlowStage $stage): array
    {
        $channels = $stage->notify_channels;

        return is_array($channels) && $channels !== [] ? $channels : ['in_app'];
    }

    /**
     * The active AidAwaitingApproval template body for a channel, with the
     * stage/aid placeholders substituted, or null when no active template
     * exists for that channel.
     *
     * @param  array<string, string>  $vars
     */
    private function renderTemplate(MessageChannel $channel, array $vars): ?string
    {
        $template = NotificationTemplate::query()
            ->activeFor(NotificationEvent::AidAwaitingApproval, $channel)
            ->first();

        if (! $template) {
            return null;
        }

        return strtr($template->body, $vars);
    }

    /**
     * @return array<string, string>
     */
    private function buildVars(Aid $aid, ApprovalFlowStage $stage): array
    {
        return [
            '{reference}' => (string) $aid->reference,
            '{program}' => $aid->program?->name ?? '',
            '{stage}' => (string) $stage->name,
            '{beneficiary}' => $aid->beneficiary?->full_name ?? '',
            '{link}' => route('approvals.inbox'),
        ];
    }
}

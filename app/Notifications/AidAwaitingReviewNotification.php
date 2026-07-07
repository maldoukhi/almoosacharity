<?php

namespace App\Notifications;

use App\Models\Aid;
use App\Models\ApprovalFlowStage;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Alerts a stage's role-holders (in-app bell + email) that an aid has
 * arrived at their approval stage and is waiting on their decision.
 */
class AidAwaitingReviewNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Aid $aid,
        public readonly ApprovalFlowStage $stage,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'lang_key' => 'notifications.awaiting_review.body',
            'aid_id' => $this->aid->id,
            'reference' => $this->aid->reference,
            'stage_name' => $this->stage->name,
            'beneficiary_name' => $this->aid->beneficiary?->full_name,
            'url' => route('aids.show', $this->aid),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $name = $notifiable instanceof User ? $notifiable->name : '';

        return (new MailMessage)
            ->subject(__('notifications.mail.awaiting_review.subject'))
            ->greeting(__('notifications.mail.awaiting_review.greeting', ['name' => $name]))
            ->line(__('notifications.mail.awaiting_review.line', [
                'reference' => $this->aid->reference,
                'beneficiary' => $this->aid->beneficiary?->full_name,
                'stage' => $this->stage->name,
            ]))
            ->action(__('notifications.mail.awaiting_review.action'), route('aids.show', $this->aid))
            ->line(__('notifications.mail.awaiting_review.footer'));
    }
}

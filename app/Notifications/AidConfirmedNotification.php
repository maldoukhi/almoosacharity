<?php

namespace App\Notifications;

use App\Models\Aid;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * In-app (bell) alert for the aid's creator and every active manager as
 * soon as the beneficiary confirms receipt via the public link. Database
 * only — per CLAUDE.md, the confirmation link itself is documentary and
 * this is an internal heads-up, not an action the recipient needs
 * e-mailed to them.
 */
class AidConfirmedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Aid $aid) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'lang_key' => 'notifications.confirmed.body',
            'aid_id' => $this->aid->id,
            'reference' => $this->aid->reference,
            'beneficiary_name' => $this->aid->beneficiary?->full_name,
            'url' => route('aids.show', $this->aid),
        ];
    }
}

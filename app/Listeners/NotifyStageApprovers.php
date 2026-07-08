<?php

namespace App\Listeners;

use App\Events\Approvals\AidEnteredStage;
use App\Models\User;
use App\Notifications\AidAwaitingReviewNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

/**
 * Notifies every active user holding the role assigned to an approval
 * stage as soon as an aid becomes current at that stage (in-app bell +
 * email), per CLAUDE.md's "manager notifications" decision.
 */
class NotifyStageApprovers implements ShouldQueue
{
    public function handle(AidEnteredStage $event): void
    {
        // Everyone eligible for the stage: its role's holders unioned with
        // any specifically-assigned users (both active only).
        $recipients = $event->stage->eligibleUsers();

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new AidAwaitingReviewNotification($event->aid, $event->stage));
    }
}

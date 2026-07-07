<?php

namespace App\Jobs\Confirmations;

use App\Actions\Confirmations\ResendConfirmationLink;
use App\Models\AidConfirmation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Daily sweep (see routes/console.php) that sends exactly one automatic
 * reminder for each outstanding confirmation link: sent at least
 * reminder_after_days ago, never reminded, not confirmed, and not yet
 * expired. Rotates the token (a fresh signed link, same channel) via
 * {@see ResendConfirmationLink} in its "system reminder" mode, which
 * preserves the original sent_at and records reminder_sent_at instead.
 */
class SendConfirmationReminders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(ResendConfirmationLink $resend): void
    {
        $threshold = now()->subDays((int) config('confirmations.reminder_after_days'));

        AidConfirmation::query()
            ->whereNull('confirmed_at')
            ->whereNull('reminder_sent_at')
            ->where('expires_at', '>', now())
            ->whereNotNull('sent_at')
            ->where('sent_at', '<=', $threshold)
            ->each(function (AidConfirmation $confirmation) use ($resend): void {
                $resend->handle($confirmation, actor: null, isReminder: true);
            });
    }
}

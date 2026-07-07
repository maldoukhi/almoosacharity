<?php

namespace App\Actions\Confirmations;

use App\Jobs\Confirmations\SendConfirmationReminders;
use App\Models\AidConfirmation;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;

/**
 * Rotates an aid's confirmation token and re-sends the link — either
 * because an admin explicitly asked to resend it, or because the daily
 * reminder job caught an outstanding, unconfirmed, non-expired link past
 * its reminder threshold (see {@see SendConfirmationReminders}).
 */
class ResendConfirmationLink
{
    public function __construct(
        private readonly CreateAidConfirmation $create,
        private readonly SendConfirmationLink $send,
    ) {}

    /**
     * @param  User|null  $actor  Null for the system-triggered reminder
     *                            flow, which skips the `resend` policy check (there is no acting
     *                            user to authorize) and preserves the original sent_at/opened_at
     *                            tracking instead of resetting it.
     *
     * @throws AuthorizationException
     */
    public function handle(AidConfirmation $confirmation, ?User $actor = null, bool $isReminder = false): AidConfirmation
    {
        if (! $isReminder) {
            Gate::forUser($actor)->authorize('resend', $confirmation);
        }

        $aid = $confirmation->aid;

        ['confirmation' => $fresh, 'rawToken' => $rawToken] = $this->create->handle($aid, preserveTracking: $isReminder);

        $this->send->handle($fresh, $rawToken, preserveSentAt: $isReminder);

        if ($isReminder) {
            $fresh->update(['reminder_sent_at' => now()]);
        }

        activity('aid-confirmation')
            ->causedBy($actor)
            ->performedOn($fresh)
            ->withProperties(['reminder' => $isReminder])
            ->log($isReminder ? 'confirmation reminder sent' : 'confirmation link resent');

        return $fresh->fresh();
    }
}

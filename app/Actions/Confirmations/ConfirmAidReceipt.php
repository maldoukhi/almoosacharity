<?php

namespace App\Actions\Confirmations;

use App\Enums\AidStatus;
use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\Aid;
use App\Models\AidConfirmation;
use App\Models\User;
use App\Notifications\AidConfirmedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * Records the beneficiary's own confirmation of an aid's delivery from
 * the public link: idempotent (a second click on an already-confirmed
 * link is a silent no-op), moves the aid delivered -> confirmed, and
 * alerts the aid's creator plus every active manager in-app.
 */
class ConfirmAidReceipt
{
    public function handle(AidConfirmation $confirmation, string $ip, string $userAgent): void
    {
        DB::transaction(function () use ($confirmation, $ip, $userAgent): void {
            $locked = AidConfirmation::query()->whereKey($confirmation->id)->lockForUpdate()->first();

            if ($locked === null || $locked->confirmed_at !== null) {
                // Already confirmed (or the row vanished under us): nothing
                // more to do. The public component itself also guards
                // against re-showing the confirm screen for an already
                // confirmed link, this is the last-line defense.
                return;
            }

            $locked->update([
                'confirmed_at' => now(),
                'confirmed_ip' => $ip,
                'confirmed_user_agent' => mb_substr($userAgent, 0, 1000),
            ]);

            $lockedAid = Aid::query()->whereKey($locked->aid_id)->lockForUpdate()->first();

            if ($lockedAid !== null && $lockedAid->status->canTransitionTo(AidStatus::Confirmed)) {
                $lockedAid->update(['status' => AidStatus::Confirmed]);
            }

            activity('aid-confirmation')
                ->performedOn($locked)
                ->log('beneficiary confirmed receipt');

            if ($lockedAid !== null) {
                $this->notifyStaff($lockedAid);
            }
        });
    }

    private function notifyStaff(Aid $aid): void
    {
        $recipients = User::role(RoleName::Manager->value)
            ->where('status', UserStatus::Active)
            ->get();

        if ($aid->created_by !== null) {
            $creator = User::query()->find($aid->created_by);

            if ($creator !== null) {
                $recipients->push($creator);
            }
        }

        $recipients = $recipients->unique('id');

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new AidConfirmedNotification($aid));
    }
}

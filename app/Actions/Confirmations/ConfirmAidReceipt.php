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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Records the beneficiary's own confirmation of an aid's delivery from
 * the public link: idempotent (a second click on an already-confirmed
 * link is a silent no-op), moves the aid delivered -> confirmed, and
 * alerts the aid's creator plus every active manager in-app.
 */
class ConfirmAidReceipt
{
    public function handle(AidConfirmation $confirmation, string $ip, string $userAgent, string $signature = ''): void
    {
        DB::transaction(function () use ($confirmation, $ip, $userAgent, $signature): void {
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

            $this->attachSignature($locked, $signature);

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

    /**
     * The largest signature payload we accept (raw data-URL length). A PNG
     * from a small canvas is a few KB; this generous ~2.25 MB ceiling stops
     * an unauthenticated caller from posting an oversized blob.
     */
    private const MAX_SIGNATURE_LENGTH = 3_000_000;

    /**
     * Persist the beneficiary's captured signature (a
     * `data:image/png;base64,…` string from the confirm-page canvas) onto
     * the confirmation, if one was drawn. Mirrors Disbursements\Panel's own
     * signature capture.
     *
     * This runs on an unauthenticated public request, so the input is
     * treated as hostile: only png/jpeg data URLs are accepted, the payload
     * is length-capped, the base64 must decode strictly, and any decode/
     * store failure is swallowed (a bad signature must never fail the
     * confirmation itself, which is the meaningful action here).
     */
    private function attachSignature(AidConfirmation $confirmation, string $signature): void
    {
        $signature = trim($signature);

        if ($signature === '' || strlen($signature) > self::MAX_SIGNATURE_LENGTH) {
            return;
        }

        if (! preg_match('#^data:image/(png|jpeg);base64,#', $signature)) {
            return;
        }

        $base64 = preg_replace('#^data:image/(png|jpeg);base64,#', '', $signature) ?? '';

        // Strict decode: reject anything that isn't valid, canonical base64.
        if ($base64 === '' || base64_decode($base64, true) === false) {
            return;
        }

        try {
            $confirmation->addMediaFromBase64($base64)
                ->usingFileName('confirmation-signature-'.$confirmation->id.'.png')
                ->toMediaCollection('confirmation_signature');
        } catch (\Throwable $e) {
            // A malformed image must not 500 the public confirmation flow.
            Log::warning('Failed to store confirmation signature.', [
                'aid_confirmation_id' => $confirmation->id,
                'error' => $e->getMessage(),
            ]);
        }
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

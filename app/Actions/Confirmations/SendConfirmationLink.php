<?php

namespace App\Actions\Confirmations;

use App\Models\AidConfirmation;
use App\Services\Messaging\Messenger;
use App\Support\Settings;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

/**
 * Builds the signed, single-purpose confirmation URL for an
 * {@see AidConfirmation} and sends it to the beneficiary's mobile over
 * SMS (always) and WhatsApp (when the whatsapp_enabled setting is on),
 * both via {@see Messenger} so the send itself is queued.
 */
class SendConfirmationLink
{
    public function __construct(
        private readonly Messenger $messenger,
        private readonly Settings $settings,
    ) {}

    /**
     * @param  bool  $preserveSentAt  True for the automatic reminder flow:
     *                                keeps the confirmation's original sent_at value (the reminder's own
     *                                timestamp is recorded separately, in reminder_sent_at, by the
     *                                caller) instead of overwriting it with "now".
     */
    public function handle(AidConfirmation $confirmation, string $rawToken, bool $preserveSentAt = false): void
    {
        $aid = $confirmation->aid()->with('beneficiary')->first();
        $beneficiary = $aid?->beneficiary;

        if (blank($beneficiary?->mobile)) {
            Log::info('Skipped aid confirmation link send: no mobile number on file.', [
                'aid_confirmation_id' => $confirmation->id,
            ]);

            return;
        }

        $link = URL::temporarySignedRoute(
            'public.confirm',
            $confirmation->expires_at,
            ['confirmation' => $confirmation->id, 'token' => $rawToken],
        );

        // The body is admin-editable from the notifications settings screen
        // (validated to always contain the {link} placeholder); fall back to
        // the shipped default when nothing has been saved.
        $template = $this->settings->get('confirmation_body') ?: __('confirmations.default_body');

        $body = strtr($template, [
            '{name}' => $beneficiary->full_name,
            '{link}' => $link,
        ]);

        $channel = 'sms';

        $this->messenger->sms($beneficiary->mobile, $body, related: $aid);

        if ($this->settings->get('whatsapp_enabled') === '1') {
            $this->messenger->whatsappText(
                $beneficiary->mobile,
                $body,
                related: $aid,
                idempotencyKey: "aid-confirmation-{$confirmation->id}-{$rawToken}-wa",
            );

            $channel = 'sms+whatsapp';
        }

        $confirmation->update([
            'sent_at' => $preserveSentAt ? ($confirmation->sent_at ?? now()) : now(),
            'channel' => $channel,
        ]);

        activity('aid-confirmation')
            ->performedOn($confirmation)
            ->log('confirmation link sent');
    }
}

<?php

namespace App\Actions\Confirmations;

use App\Enums\MessageChannel;
use App\Enums\NotificationEvent;
use App\Models\AidConfirmation;
use App\Models\Beneficiary;
use App\Services\Messaging\Messenger;
use App\Services\Notifications\NotifyBeneficiary;
use App\Support\ConfirmationLink;
use App\Support\Settings;
use Illuminate\Support\Facades\Log;

/**
 * Builds the short confirmation URL for an {@see AidConfirmation} and
 * sends it to the beneficiary's mobile over SMS (always) and WhatsApp
 * (when the whatsapp_enabled setting is on), both via {@see Messenger} so
 * the send itself is queued.
 *
 * Two distinct message sources, selected by the combined_delivery_message
 * setting:
 *
 *  - Off (default): a single admin-editable "confirmation_body" setting,
 *    shared by SMS and WhatsApp, which must contain {link}.
 *  - On: there is no separate confirmation text at all — the aid's
 *    "delivered" event template *is* the confirmation message (edited in
 *    one place, per channel, in the normal templates screen) and must
 *    itself contain {link}. This is what lets the delivery notice and the
 *    confirmation link live in a single, directly-edited template instead
 *    of two texts stitched together at send time.
 */
class SendConfirmationLink
{
    public function __construct(
        private readonly Messenger $messenger,
        private readonly Settings $settings,
        private readonly ConfirmationLink $confirmationLink,
        private readonly NotifyBeneficiary $notifyBeneficiary,
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

        if (blank($beneficiary?->mobile) || $aid === null) {
            Log::info('Skipped aid confirmation link send: no mobile number on file.', [
                'aid_confirmation_id' => $confirmation->id,
            ]);

            return;
        }

        $link = $this->confirmationLink->url($confirmation, $rawToken);
        $combined = $this->settings->get('combined_delivery_message') === '1';

        $smsBody = $combined
            ? $this->notifyBeneficiary->renderBody($aid, NotificationEvent::AidDelivered, MessageChannel::Sms, ['link' => $link])
            : $this->renderStandaloneBody($beneficiary, $link);

        if (blank($smsBody)) {
            Log::info('Skipped aid confirmation link send: no message body to send.', [
                'aid_confirmation_id' => $confirmation->id,
                'combined_delivery_message' => $combined,
            ]);

            return;
        }

        // Safety net: Manage's save validation requires {link} inside the
        // AidDelivered template whenever combined mode is on, but this
        // setting can also be toggled directly (tests, tinker, a future
        // admin tool) without going through that validation — never leave
        // the beneficiary with a message that has no way to confirm.
        $smsBody = $this->ensureLinkPresent($smsBody, $link);

        $channel = 'sms';

        $this->messenger->sms($beneficiary->mobile, $smsBody, related: $aid);

        if ($this->settings->get('whatsapp_enabled') === '1') {
            $whatsappBody = $combined
                ? ($this->notifyBeneficiary->renderBody($aid, NotificationEvent::AidDelivered, MessageChannel::WhatsApp, ['link' => $link]) ?? $smsBody)
                : $smsBody;

            $whatsappBody = $this->ensureLinkPresent($whatsappBody, $link);

            $this->messenger->whatsappText(
                $beneficiary->mobile,
                $whatsappBody,
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

    /**
     * The non-combined message body: the admin-editable "confirmation_body"
     * setting (validated on save to always contain {link}), falling back to
     * the shipped default when nothing has been saved yet.
     */
    private function renderStandaloneBody(Beneficiary $beneficiary, string $link): string
    {
        $template = $this->settings->get('confirmation_body') ?: __('confirmations.default_body');

        return strtr($template, [
            '{name}' => $beneficiary->full_name,
            '{short_name}' => $beneficiary->short_name,
            '{link}' => $link,
        ]);
    }

    private function ensureLinkPresent(string $body, string $link): string
    {
        return str_contains($body, $link) ? $body : $body."\n".$link;
    }
}

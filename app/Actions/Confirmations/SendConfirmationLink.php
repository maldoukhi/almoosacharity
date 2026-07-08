<?php

namespace App\Actions\Confirmations;

use App\Enums\MessageChannel;
use App\Enums\NotificationEvent;
use App\Models\AidConfirmation;
use App\Services\Messaging\Messenger;
use App\Services\Notifications\NotifyBeneficiary;
use App\Support\ConfirmationLink;
use App\Support\Settings;
use Illuminate\Support\Facades\Log;

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

        if (blank($beneficiary?->mobile)) {
            Log::info('Skipped aid confirmation link send: no mobile number on file.', [
                'aid_confirmation_id' => $confirmation->id,
            ]);

            return;
        }

        $link = $this->confirmationLink->url($confirmation, $rawToken);

        // The body is admin-editable from the notifications settings screen
        // (validated to always contain the {link} placeholder); fall back to
        // the shipped default when nothing has been saved.
        $template = $this->settings->get('confirmation_body') ?: __('confirmations.default_body');

        $body = strtr($template, [
            '{name}' => $beneficiary->full_name,
            '{short_name}' => $beneficiary->short_name,
            '{link}' => $link,
        ]);

        // Combined delivery message: when the admin has turned this on, the
        // beneficiary should receive a *single* message on delivery carrying
        // both the "aid delivered" notice and the confirmation link (the
        // standalone delivery notification is suppressed in
        // SendBeneficiaryAidNotification). We only fold the notice into the
        // first send at delivery time, never into the later reminder
        // (preserveSentAt), and only when the AidDelivered template is active.
        if (! $preserveSentAt && $this->settings->get('combined_delivery_message') === '1') {
            $deliveryNotice = $this->notifyBeneficiary->renderBody(
                $aid,
                NotificationEvent::AidDelivered,
                MessageChannel::Sms,
            );

            if ($deliveryNotice !== null && trim($deliveryNotice) !== '') {
                $body = $deliveryNotice."\n\n".$body;
            }
        }

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

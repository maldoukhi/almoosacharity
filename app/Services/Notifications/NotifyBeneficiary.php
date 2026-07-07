<?php

namespace App\Services\Notifications;

use App\Enums\AidType;
use App\Enums\MessageChannel;
use App\Enums\NotificationEvent;
use App\Models\Aid;
use App\Models\NotificationTemplate;
use App\Services\Messaging\Messenger;
use App\Support\Settings;
use Illuminate\Support\Facades\Log;

/**
 * Sends a beneficiary-facing SMS/WhatsApp message for a given aid
 * lifecycle event, driven entirely by the active {@see NotificationTemplate}
 * rows and the sms_enabled/whatsapp_enabled settings toggles — never
 * hardcoded copy.
 */
class NotifyBeneficiary
{
    public function __construct(
        private readonly Messenger $messenger,
        private readonly Settings $settings,
    ) {}

    public function send(Aid $aid, NotificationEvent $event): void
    {
        $beneficiary = $aid->beneficiary;

        if (blank($beneficiary?->mobile)) {
            Log::info('Skipped beneficiary aid notification: no mobile number on file.', [
                'aid_id' => $aid->id,
                'event' => $event->value,
            ]);

            return;
        }

        $vars = [
            'name' => $beneficiary->full_name,
            'amount' => $aid->type === AidType::Cash ? number_format((float) $aid->amount, 2) : '',
            'program' => $aid->program?->name ?? '',
        ];

        $this->sendChannel(MessageChannel::Sms, $aid, $beneficiary->mobile, $event, $vars);
        $this->sendChannel(MessageChannel::WhatsApp, $aid, $beneficiary->mobile, $event, $vars);
    }

    /**
     * @param  array<string, string>  $vars
     */
    private function sendChannel(MessageChannel $channel, Aid $aid, string $mobile, NotificationEvent $event, array $vars): void
    {
        if (! $this->channelEnabled($channel)) {
            return;
        }

        $template = NotificationTemplate::query()->activeFor($event, $channel)->first();

        if (! $template) {
            return;
        }

        $body = strtr($template->body, [
            '{name}' => $vars['name'],
            '{amount}' => $vars['amount'],
            '{program}' => $vars['program'],
        ]);

        match ($channel) {
            MessageChannel::Sms => $this->messenger->sms($mobile, $body, related: $aid),
            MessageChannel::WhatsApp => $this->messenger->whatsappText(
                $mobile,
                $body,
                related: $aid,
                idempotencyKey: "aid-{$aid->id}-{$event->value}-wa",
            ),
        };
    }

    private function channelEnabled(MessageChannel $channel): bool
    {
        $key = match ($channel) {
            MessageChannel::Sms => 'sms_enabled',
            MessageChannel::WhatsApp => 'whatsapp_enabled',
        };

        return $this->settings->get($key) === '1';
    }
}

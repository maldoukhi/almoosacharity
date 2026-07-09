<?php

namespace App\Services\Notifications;

use App\Enums\AidType;
use App\Enums\MessageChannel;
use App\Enums\NotificationEvent;
use App\Models\Aid;
use App\Models\Beneficiary;
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

        $vars = $this->buildVars($aid, $beneficiary);

        $this->sendChannel(MessageChannel::Sms, $aid, $beneficiary->mobile, $event, $vars);
        $this->sendChannel(MessageChannel::WhatsApp, $aid, $beneficiary->mobile, $event, $vars);
    }

    /**
     * Render the active {@see NotificationTemplate} body for a given
     * event/channel pair with all placeholders substituted, or null when no
     * active template exists (or the aid has no beneficiary). Used by the
     * combined delivery-message flow, where the "aid delivered" template
     * *is* the confirmation message (it carries `{link}` directly) — this
     * does not itself send anything and does not consult the
     * channel-enabled toggles.
     *
     * @param  array<string, string>  $extra  Extra placeholder values merged
     *                                        in on top of the usual aid/beneficiary vars — e.g. `['link' => $url]`
     *                                        so a template can reference `{link}`.
     */
    public function renderBody(Aid $aid, NotificationEvent $event, MessageChannel $channel, array $extra = []): ?string
    {
        $beneficiary = $aid->beneficiary;

        if ($beneficiary === null) {
            return null;
        }

        $template = NotificationTemplate::query()->activeFor($event, $channel)->first();

        if (! $template) {
            return null;
        }

        return $this->render($template->body, $this->buildVars($aid, $beneficiary) + $extra);
    }

    /**
     * The placeholder values for an aid/beneficiary, shared by every
     * outbound body render.
     *
     * @return array<string, string>
     */
    private function buildVars(Aid $aid, Beneficiary $beneficiary): array
    {
        return [
            'name' => $beneficiary->full_name,
            'short_name' => $beneficiary->short_name,
            'amount' => $aid->type === AidType::Cash ? number_format((float) $aid->amount, 2) : '',
            'program' => $aid->program?->name ?? '',
        ];
    }

    /**
     * @param  array<string, string>  $vars
     */
    private function render(string $body, array $vars): string
    {
        return strtr($body, [
            '{name}' => $vars['name'],
            '{short_name}' => $vars['short_name'],
            '{amount}' => $vars['amount'],
            '{program}' => $vars['program'],
            '{link}' => $vars['link'] ?? '',
        ]);
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

        $body = $this->render($template->body, $vars);

        match ($channel) {
            MessageChannel::Sms => $this->messenger->sms($mobile, $body, related: $aid),
            MessageChannel::WhatsApp => $this->messenger->whatsappText(
                $mobile,
                $body,
                related: $aid,
                idempotencyKey: "aid-{$aid->id}-{$event->value}-wa",
            ),
            // Beneficiaries are reached only by SMS/WhatsApp (no email on
            // file); any other channel is a no-op here rather than a throw.
            default => null,
        };
    }

    private function channelEnabled(MessageChannel $channel): bool
    {
        $key = match ($channel) {
            MessageChannel::Sms => 'sms_enabled',
            MessageChannel::WhatsApp => 'whatsapp_enabled',
            // Non-beneficiary channels (e.g. Email) are never enabled for
            // beneficiary notifications.
            default => null,
        };

        if ($key === null) {
            return false;
        }

        return $this->settings->get($key) === '1';
    }
}

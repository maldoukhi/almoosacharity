<?php

namespace App\Services\Messaging;

use App\Enums\MessageChannel;
use App\Enums\MessageStatus;
use App\Jobs\Messaging\SendEmailMessage;
use App\Jobs\Messaging\SendSmsMessage;
use App\Jobs\Messaging\SendWhatsAppMessage;
use App\Models\MessageLog;
use Illuminate\Database\Eloquent\Model;

/**
 * Single entry point the rest of the app (Aids, Approvals, Confirmations,
 * Surveys...) uses to send an SMS or WhatsApp message. Creates the
 * {@see MessageLog} row up front (status: pending) and dispatches the
 * queued job that actually talks to the provider.
 */
class Messenger
{
    /**
     * Queue a plain-text SMS.
     */
    public function sms(string $to, string $body, ?Model $related = null): MessageLog
    {
        $log = $this->createLog(
            channel: MessageChannel::Sms,
            provider: (string) config('services.sms.driver', 'fake'),
            to: $to,
            body: $body,
            related: $related,
        );

        SendSmsMessage::dispatch($log->id);

        return $log;
    }

    /**
     * Queue a free-form text WhatsApp message.
     */
    public function whatsappText(
        string $to,
        string $body,
        ?Model $related = null,
        ?string $idempotencyKey = null,
    ): MessageLog {
        $log = $this->createLog(
            channel: MessageChannel::WhatsApp,
            provider: (string) config('services.whatsapp.driver', 'fake'),
            to: $to,
            body: $body,
            related: $related,
        );

        SendWhatsAppMessage::dispatch(
            $log->id,
            $to,
            body: $body,
            idempotencyKey: $idempotencyKey ?? $this->defaultIdempotencyKey($log),
        );

        return $log;
    }

    /**
     * Queue a WhatsApp media message: an attached document/image the
     * provider fetches from $mediaUrl (a public HTTPS URL), with $caption as
     * the accompanying text. message_logs.body stores the caption so the
     * audit trail still carries a human-readable value.
     */
    public function whatsappMedia(
        string $to,
        string $mediaType,
        string $mediaUrl,
        string $caption = '',
        ?Model $related = null,
        ?string $idempotencyKey = null,
    ): MessageLog {
        $log = $this->createLog(
            channel: MessageChannel::WhatsApp,
            provider: (string) config('services.whatsapp.driver', 'fake'),
            to: $to,
            body: $caption,
            related: $related,
        );

        SendWhatsAppMessage::dispatch(
            $log->id,
            $to,
            body: $caption,
            idempotencyKey: $idempotencyKey ?? $this->defaultIdempotencyKey($log),
            mediaUrl: $mediaUrl,
            mediaType: $mediaType,
        );

        return $log;
    }

    /**
     * Queue a Meta-approved WhatsApp template message.
     *
     * @param  array<string, mixed>  $variables
     */
    public function whatsappTemplate(
        string $to,
        string $templateName,
        array $variables,
        string $language = 'ar',
        ?Model $related = null,
        ?string $idempotencyKey = null,
    ): MessageLog {
        $log = $this->createLog(
            channel: MessageChannel::WhatsApp,
            provider: (string) config('services.whatsapp.driver', 'fake'),
            to: $to,
            body: $this->renderTemplatePreview($templateName, $variables),
            related: $related,
            templateName: $templateName,
        );

        SendWhatsAppMessage::dispatch(
            $log->id,
            $to,
            templateName: $templateName,
            variables: $variables,
            language: $language,
            idempotencyKey: $idempotencyKey ?? $this->defaultIdempotencyKey($log),
        );

        return $log;
    }

    /**
     * Queue a plain-text email (rendered from a template upstream), with an
     * optional file attachment.
     *
     * @param  array{disk: string, path: string, name?: string}|null  $attachment
     */
    public function email(
        string $to,
        string $subject,
        string $body,
        ?Model $related = null,
        ?array $attachment = null,
    ): MessageLog {
        $log = $this->createLog(
            channel: MessageChannel::Email,
            provider: (string) config('mail.default', 'log'),
            to: $to,
            body: $body,
            related: $related,
        );

        SendEmailMessage::dispatch($log->id, $to, $subject, $body, $attachment);

        return $log;
    }

    protected function createLog(
        MessageChannel $channel,
        string $provider,
        string $to,
        string $body,
        ?Model $related,
        ?string $templateName = null,
    ): MessageLog {
        return MessageLog::create([
            'channel' => $channel,
            'provider' => $provider,
            'recipient' => $to,
            'body' => $body,
            'template_name' => $templateName,
            'status' => MessageStatus::Pending,
            'attempts' => 0,
            'messageable_type' => $related?->getMorphClass(),
            'messageable_id' => $related?->getKey(),
        ]);
    }

    /**
     * A stable default idempotency key when the caller doesn't supply a
     * business-meaningful one (e.g. `aid-{id}-approved`). Tied to the
     * message_logs row so retries of the same send share one key and the
     * provider can safely dedupe them.
     */
    protected function defaultIdempotencyKey(MessageLog $log): string
    {
        return 'message-log-'.$log->id;
    }

    /**
     * Templates are rendered server-side by the WhatsApp provider from an
     * approved catalogue entry; message_logs.body still needs a
     * human-readable value for auditing/listing, so we store a simple
     * preview rather than leaving it empty.
     *
     * @param  array<string, mixed>  $variables
     */
    protected function renderTemplatePreview(string $templateName, array $variables): string
    {
        $values = array_map(static fn ($value): string => (string) $value, array_values($variables));

        return sprintf('[%s] %s', $templateName, implode(', ', $values));
    }
}

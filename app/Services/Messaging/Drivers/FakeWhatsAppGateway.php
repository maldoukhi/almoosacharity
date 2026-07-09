<?php

namespace App\Services\Messaging\Drivers;

use App\Services\Messaging\Contracts\WhatsAppChannelPairingInterface;
use App\Services\Messaging\Contracts\WhatsAppGatewayInterface;
use App\Services\Messaging\GatewayResponse;
use Illuminate\Support\Str;

/**
 * In-memory WhatsApp gateway used locally and in tests. Always succeeds
 * unless explicitly told to fail, mirroring {@see FakeSmsGateway}.
 *
 * Deliberately does *not* implement
 * {@see WhatsAppChannelPairingInterface}:
 * QR channel pairing is a real capability of the Okta Connect driver
 * specifically (see {@see OktaWhatsAppGateway}), not a generic property of
 * "some WhatsApp gateway" — leaving it unimplemented here is what lets the
 * settings screen's `instanceof` capability check mean something.
 */
class FakeWhatsAppGateway implements WhatsAppGatewayInterface
{
    /**
     * @var list<array<string, mixed>>
     */
    public static array $sent = [];

    protected static bool $shouldFail = false;

    protected static ?string $failureMessage = null;

    public function sendText(string $to, string $message, ?string $idempotencyKey = null): GatewayResponse
    {
        return $this->record('text', $to, [
            'message' => $message,
            'idempotency_key' => $idempotencyKey,
        ]);
    }

    public function sendMedia(
        string $to,
        string $type,
        string $mediaUrl,
        string $caption = '',
        ?string $idempotencyKey = null,
    ): GatewayResponse {
        return $this->record('media', $to, [
            'media_type' => $type,
            'media_url' => $mediaUrl,
            'caption' => $caption,
            'idempotency_key' => $idempotencyKey,
        ]);
    }

    public function sendTemplate(
        string $to,
        string $templateName,
        array $variables,
        string $language = 'ar',
        ?string $idempotencyKey = null,
    ): GatewayResponse {
        return $this->record('template', $to, [
            'template_name' => $templateName,
            'variables' => $variables,
            'language' => $language,
            'idempotency_key' => $idempotencyKey,
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function record(string $type, string $to, array $payload): GatewayResponse
    {
        if (static::$shouldFail) {
            return GatewayResponse::failure(static::$failureMessage ?? 'Fake WhatsApp gateway forced failure.');
        }

        $entry = ['type' => $type, 'to' => $to] + $payload;
        static::$sent[] = $entry;

        return GatewayResponse::success('fake-wa-'.Str::uuid(), $entry);
    }

    /**
     * Always "succeeds" (mirroring a healthy, connected channel) unless
     * {@see failNext()} was called.
     */
    public function verify(): GatewayResponse
    {
        if (static::$shouldFail) {
            return GatewayResponse::failure(static::$failureMessage ?? 'Fake WhatsApp gateway forced failure.');
        }

        return GatewayResponse::success('fake-channel', ['id' => 'fake-channel', 'status' => 'connected']);
    }

    public static function failNext(?string $message = null): void
    {
        static::$shouldFail = true;
        static::$failureMessage = $message;
    }

    public static function reset(): void
    {
        static::$sent = [];
        static::$shouldFail = false;
        static::$failureMessage = null;
    }
}

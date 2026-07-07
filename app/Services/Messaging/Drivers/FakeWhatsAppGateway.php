<?php

namespace App\Services\Messaging\Drivers;

use App\Services\Messaging\Contracts\WhatsAppGatewayInterface;
use App\Services\Messaging\GatewayResponse;
use Illuminate\Support\Str;

/**
 * In-memory WhatsApp gateway used locally and in tests. Always succeeds
 * unless explicitly told to fail, mirroring {@see FakeSmsGateway}.
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

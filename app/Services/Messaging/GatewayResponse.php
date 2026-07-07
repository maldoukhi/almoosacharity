<?php

namespace App\Services\Messaging;

/**
 * Uniform result returned by every SMS/WhatsApp gateway driver, regardless
 * of the underlying provider's response shape.
 */
final class GatewayResponse
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $providerMessageId = null,
        public readonly array $raw = [],
        public readonly ?string $error = null,
        public readonly ?int $retryAfter = null,
    ) {}

    /**
     * @param  array<string, mixed>  $raw
     */
    public static function success(?string $providerMessageId = null, array $raw = []): self
    {
        return new self(success: true, providerMessageId: $providerMessageId, raw: $raw);
    }

    /**
     * @param  array<string, mixed>  $raw
     */
    public static function failure(?string $error, array $raw = [], ?int $retryAfter = null): self
    {
        return new self(success: false, raw: $raw, error: $error, retryAfter: $retryAfter);
    }
}

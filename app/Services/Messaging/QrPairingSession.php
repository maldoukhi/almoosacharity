<?php

namespace App\Services\Messaging;

use App\Services\Messaging\Contracts\WhatsAppChannelPairingInterface;

/**
 * Provider-agnostic snapshot of one QR-pairing poll cycle, decoupling
 * Livewire/UI code from any single provider's own DTO (e.g. the Okta
 * Connect SDK's `Okta\Connect\WhatsApp\DTO\QrSession`). Returned by
 * {@see WhatsAppChannelPairingInterface}.
 */
final readonly class QrPairingSession
{
    public function __construct(
        public string $channelId,
        public string $displayName,
        public string $status,
        public ?string $qr,
        public ?int $qrTtlSeconds,
    ) {}

    public function isConnected(): bool
    {
        return $this->status === 'connected';
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, ['connected', 'disconnected', 'failed'], true);
    }
}

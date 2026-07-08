<?php

namespace App\Services\Messaging\Contracts;

use App\Services\Messaging\QrPairingSession;

/**
 * Optional capability implemented only by WhatsApp gateway drivers whose
 * provider supports pairing a channel by scanning a QR code.
 *
 * Confirmed by reading vendor/getokta/okta-connect-sdk/src (Client::qr(),
 * Resources/Integrations/QrPairing.php, DTO/QrSession.php, CHANGELOG.md
 * 0.4.0): this is a *companion* flow to WhatsApp Cloud API's embedded
 * signup (`Client::meta()`), for numbers that instead run on a
 * Baileys-backed channel — the SDK does not expose QR pairing for Cloud
 * API channels, and Meta channels don't need it (they pair via
 * `Client::meta()->completeEmbeddedSignup()` instead). Callers must check
 * `instanceof` before using this — not every {@see WhatsAppGatewayInterface}
 * driver/channel type supports it.
 */
interface WhatsAppChannelPairingInterface
{
    /**
     * Start a new QR pairing session, provisioning a new channel with the
     * given display name. The caller polls {@see qrPairingStatus()} with
     * the returned session's channel id until it reaches a terminal
     * status (`connected`, `disconnected` or `failed`).
     */
    public function startQrPairing(string $displayName): QrPairingSession;

    /**
     * Poll the current status (and, while still pending, the live QR
     * string + remaining TTL in seconds) of a previously-started pairing
     * session/channel.
     */
    public function qrPairingStatus(string $channelId): QrPairingSession;

    /**
     * List the channels already provisioned on the account, so an operator
     * can link an existing (already-connected) channel instead of pairing
     * a fresh one via QR.
     *
     * @return array<int, array{id: string, name: ?string, status: ?string, type: ?string}>
     */
    public function listChannels(): array;
}

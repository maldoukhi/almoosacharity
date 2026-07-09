<?php

namespace App\Services\Messaging\Contracts;

use App\Services\Messaging\GatewayResponse;

interface WhatsAppGatewayInterface
{
    /**
     * Send a free-form text WhatsApp message.
     */
    public function sendText(string $to, string $message, ?string $idempotencyKey = null): GatewayResponse;

    /**
     * Send a media message (image/document/audio/video) by public HTTPS URL,
     * with an optional text caption. The URL must be reachable by the
     * WhatsApp provider (Okta Connect) over the public internet — it fetches
     * the file itself rather than accepting an upload.
     *
     * @param  string  $type  One of: image, document, audio, video.
     */
    public function sendMedia(
        string $to,
        string $type,
        string $mediaUrl,
        string $caption = '',
        ?string $idempotencyKey = null,
    ): GatewayResponse;

    /**
     * Send a Meta-approved WhatsApp template message.
     *
     * @param  array<string, mixed>  $variables
     */
    public function sendTemplate(
        string $to,
        string $templateName,
        array $variables,
        string $language = 'ar',
        ?string $idempotencyKey = null,
    ): GatewayResponse;

    /**
     * Lightweight, side-effect-free connectivity/credential check against
     * the provider (e.g. confirming the token and configured channel id
     * both resolve) — backs the "verify connection" action on the
     * notifications settings screen. Must never send an actual message.
     */
    public function verify(): GatewayResponse;
}

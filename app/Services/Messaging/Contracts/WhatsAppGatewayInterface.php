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
}

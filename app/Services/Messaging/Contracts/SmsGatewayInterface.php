<?php

namespace App\Services\Messaging\Contracts;

use App\Services\Messaging\GatewayResponse;

interface SmsGatewayInterface
{
    /**
     * Send a plain-text SMS message.
     */
    public function send(string $to, string $message): GatewayResponse;

    /**
     * Lightweight, side-effect-free connectivity/credential check against
     * the provider (e.g. an account balance/status lookup) — backs the
     * "verify connection" action on the notifications settings screen.
     * Must never send an actual message.
     */
    public function verify(): GatewayResponse;
}

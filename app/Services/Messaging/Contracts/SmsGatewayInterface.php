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

    /**
     * List the sender names registered on the connected provider account,
     * for the notifications settings screen to offer as a pick-list
     * instead of a free-text field. Only names usable for sending (e.g.
     * accepted/approved by the provider — see the concrete driver's
     * docblock for exactly how that's determined) should be included.
     *
     * On success, `GatewayResponse::$raw['normalizedSenders']` is a
     * `list<array{name: string, status: ?string}>` — the original
     * provider payload is otherwise left untouched in `$raw` for
     * auditing. Must never send an actual message.
     */
    public function senders(): GatewayResponse;
}

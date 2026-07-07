<?php

namespace App\Services\Messaging\Contracts;

use App\Services\Messaging\GatewayResponse;

interface SmsGatewayInterface
{
    /**
     * Send a plain-text SMS message.
     */
    public function send(string $to, string $message): GatewayResponse;
}

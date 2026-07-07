<?php

namespace App\Services\Messaging\Drivers;

use App\Services\Messaging\Contracts\WhatsAppGatewayInterface;
use App\Services\Messaging\GatewayResponse;
use Okta\Connect\WhatsApp\Client;
use Okta\Connect\WhatsApp\Exceptions\RateLimitException;
use Okta\Connect\WhatsApp\Exceptions\WhatsAppException;
use Okta\Connect\WhatsApp\Http\HttpClientInterface;

/**
 * WhatsApp gateway backed by the official `getokta/okta-connect-sdk`
 * package (github.com/getokta/okta-connect-sdk, installed via Composer).
 *
 * Confirmed from reading vendor/getokta/okta-connect-sdk/src:
 *  - `Client::messages()->send(payload, idempotencyKey)` POSTs to
 *    `/api/v1/messages` with `{channel_id, to, type: 'text',
 *    text: {body}}` and an `Idempotency-Key` header.
 *  - `Client::templates()->send(payload, idempotencyKey)` POSTs to
 *    `/api/v1/templates/send` with `{channel_id, wa_id, template_name,
 *    language, variables}`.
 *  - Auth is `Authorization: Bearer {token}` (see Http/HttpClient).
 *  - `RateLimitException::retryAfter()` returns the parsed `Retry-After`
 *    header (seconds) on HTTP 429.
 *
 * Decision: the SDK's own HttpClient will *synchronously* retry 429/5xx
 * responses internally (blocking with `usleep()` for `Retry-After`
 * seconds) when `Config::$retries > 0`. That is at odds with this
 * project's rule that retry/backoff for external sends happens at the
 * Queued Job layer (`release($seconds)`), not by blocking a worker
 * process. We therefore construct the Client with `retries: 0` so any
 * 429/5xx is thrown immediately as a typed exception, and
 * `SendWhatsAppMessage` is the one that decides how long to wait before
 * the next attempt.
 */
class OktaWhatsAppGateway implements WhatsAppGatewayInterface
{
    protected readonly Client $client;

    public function __construct(
        string $baseUrl,
        string $token,
        protected readonly string $channelId,
        ?HttpClientInterface $httpClient = null,
    ) {
        $this->client = new Client($baseUrl, $token, ['retries' => 0], $httpClient);
    }

    public function sendText(string $to, string $message, ?string $idempotencyKey = null): GatewayResponse
    {
        return $this->attempt(function () use ($to, $message, $idempotencyKey) {
            return $this->client->messages()->send([
                'channel_id' => $this->channelId,
                'to' => $to,
                'type' => 'text',
                'text' => ['body' => $message],
            ], $idempotencyKey);
        });
    }

    public function sendTemplate(
        string $to,
        string $templateName,
        array $variables,
        string $language = 'ar',
        ?string $idempotencyKey = null,
    ): GatewayResponse {
        return $this->attempt(function () use ($to, $templateName, $variables, $language, $idempotencyKey) {
            return $this->client->templates()->send([
                'channel_id' => $this->channelId,
                'wa_id' => $to,
                'template_name' => $templateName,
                'language' => $language,
                'variables' => array_values($variables),
            ], $idempotencyKey);
        });
    }

    protected function attempt(callable $call): GatewayResponse
    {
        try {
            $message = $call();

            return GatewayResponse::success($message->id, $message->toArray());
        } catch (RateLimitException $e) {
            return GatewayResponse::failure($e->getMessage(), $e->responseJson(), $e->retryAfter());
        } catch (WhatsAppException $e) {
            return GatewayResponse::failure($e->getMessage(), $e->responseJson());
        }
    }
}

<?php

namespace App\Services\Messaging\Drivers;

use App\Services\Messaging\Contracts\WhatsAppChannelPairingInterface;
use App\Services\Messaging\Contracts\WhatsAppGatewayInterface;
use App\Services\Messaging\GatewayResponse;
use App\Services\Messaging\QrPairingSession;
use App\Support\MobileNumber;
use App\Support\Settings;
use Okta\Connect\WhatsApp\Client;
use Okta\Connect\WhatsApp\DTO\Channel as SdkChannel;
use Okta\Connect\WhatsApp\DTO\QrSession as SdkQrSession;
use Okta\Connect\WhatsApp\Exceptions\RateLimitException;
use Okta\Connect\WhatsApp\Exceptions\WhatsAppException;
use Okta\Connect\WhatsApp\Http\HttpClientInterface;
use RuntimeException;

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
 *  - `Client::channels()->get($id)` GETs `/api/v1/channels/{id}` — the SDK
 *    has no dedicated "ping"/"health"/"me" endpoint, so this doubles as
 *    the connectivity/credential check for {@see verify()}: a bad token
 *    surfaces as a 401 (`AuthenticationException`), an unknown channel id
 *    as a 404 (`NotFoundException`), both typed subclasses of
 *    `WhatsAppException`.
 *  - `Client::qr()->start($displayName)` / `->status($channelId)` — QR
 *    channel pairing, documented in CHANGELOG.md 0.4.0 and implemented in
 *    Resources/Integrations/QrPairing.php. This is the *only*
 *    channel-provisioning capability the SDK exposes beyond Meta's
 *    Cloud-API embedded signup (`Client::meta()`), so it's surfaced here
 *    through the optional {@see WhatsAppChannelPairingInterface} rather
 *    than folded into every driver.
 *
 * Decision: the SDK's own HttpClient will *synchronously* retry 429/5xx
 * responses internally (blocking with `usleep()` for `Retry-After`
 * seconds) when `Config::$retries > 0`. That is at odds with this
 * project's rule that retry/backoff for external sends happens at the
 * Queued Job layer (`release($seconds)`), not by blocking a worker
 * process. We therefore always construct the vendor `Client` with
 * `retries: 0` so any 429/5xx is thrown immediately as a typed exception,
 * and `SendWhatsAppMessage` is the one that decides how long to wait
 * before the next attempt.
 *
 * Settings override: `okta_base_url` / `okta_token` / `okta_channel_id`
 * are admin-editable at runtime from the notifications settings screen
 * (App\Support\Settings — the token is encrypted at rest), read fresh via
 * {@see resolvedConfig()} on *every* call and falling back to the
 * constructor defaults (config('services.okta_connect.*'), i.e. .env)
 * when no override is saved. The vendor SDK bakes baseUrl/token into its
 * `Client` at construction time, so rather than caching one `Client` for
 * this gateway's lifetime (which would go stale the moment an admin saves
 * new credentials, especially under a long-running queue worker), every
 * call builds a fresh, cheap `Client` from the currently-resolved config —
 * no network I/O happens at `Client`/`HttpClient` construction, only on
 * the actual request.
 */
class OktaWhatsAppGateway implements WhatsAppChannelPairingInterface, WhatsAppGatewayInterface
{
    public function __construct(
        protected readonly string $baseUrl,
        protected readonly string $token,
        protected readonly string $channelId,
        protected readonly ?HttpClientInterface $httpClient = null,
    ) {}

    public function sendText(string $to, string $message, ?string $idempotencyKey = null): GatewayResponse
    {
        $config = $this->resolvedConfig();

        return $this->attempt(function () use ($to, $message, $idempotencyKey, $config) {
            return $this->client($config)->messages()->send([
                'channel_id' => $config['channelId'],
                'to' => MobileNumber::toInternational($to),
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
        $config = $this->resolvedConfig();

        return $this->attempt(function () use ($to, $templateName, $variables, $language, $idempotencyKey, $config) {
            return $this->client($config)->templates()->send([
                'channel_id' => $config['channelId'],
                'wa_id' => MobileNumber::toInternational($to),
                'template_name' => $templateName,
                'language' => $language,
                'variables' => array_values($variables),
            ], $idempotencyKey);
        });
    }

    public function verify(): GatewayResponse
    {
        $config = $this->resolvedConfig();

        if ($config['baseUrl'] === '' || $config['token'] === '' || $config['channelId'] === '') {
            return GatewayResponse::failure('Okta Connect base URL, token, or channel id is not configured.');
        }

        try {
            $channel = $this->client($config)->channels()->get($config['channelId']);

            return GatewayResponse::success($channel->id, $channel->toArray());
        } catch (RateLimitException $e) {
            return GatewayResponse::failure($e->getMessage(), $e->responseJson(), $e->retryAfter());
        } catch (WhatsAppException $e) {
            return GatewayResponse::failure($e->getMessage(), $e->responseJson());
        }
    }

    public function startQrPairing(string $displayName): QrPairingSession
    {
        $config = $this->resolvedConfig();

        try {
            return $this->mapQrSession($this->client($config)->qr()->start($displayName));
        } catch (WhatsAppException $e) {
            throw new RuntimeException($e->getMessage(), previous: $e);
        }
    }

    public function qrPairingStatus(string $channelId): QrPairingSession
    {
        $config = $this->resolvedConfig();

        try {
            return $this->mapQrSession($this->client($config)->qr()->status($channelId));
        } catch (WhatsAppException $e) {
            throw new RuntimeException($e->getMessage(), previous: $e);
        }
    }

    public function listChannels(): array
    {
        $config = $this->resolvedConfig();

        try {
            $result = $this->client($config)->channels()->list();
        } catch (WhatsAppException $e) {
            throw new RuntimeException($e->getMessage(), previous: $e);
        }

        return array_map(static fn (SdkChannel $channel): array => [
            'id' => (string) $channel->id,
            'name' => $channel->displayName,
            'status' => $channel->status,
            'type' => $channel->type?->value,
        ], $result->items());
    }

    protected function mapQrSession(SdkQrSession $session): QrPairingSession
    {
        return new QrPairingSession(
            channelId: $session->id,
            displayName: $session->displayName,
            status: $session->status,
            qr: $session->qr,
            qrTtlSeconds: $session->qrTtlSeconds,
        );
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

    /**
     * @return array{baseUrl: string, token: string, channelId: string}
     */
    protected function resolvedConfig(): array
    {
        $settings = app(Settings::class);

        return [
            'baseUrl' => $settings->get('okta_base_url') ?: $this->baseUrl,
            'token' => $settings->getSecret('okta_token') ?: $this->token,
            'channelId' => $settings->get('okta_channel_id') ?: $this->channelId,
        ];
    }

    /**
     * @param  array{baseUrl: string, token: string, channelId: string}  $config
     */
    protected function client(array $config): Client
    {
        return new Client($config['baseUrl'], $config['token'], ['retries' => 0], $this->httpClient);
    }
}

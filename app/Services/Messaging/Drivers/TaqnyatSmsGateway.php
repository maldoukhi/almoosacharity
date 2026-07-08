<?php

namespace App\Services\Messaging\Drivers;

use App\Services\Messaging\Contracts\SmsGatewayInterface;
use App\Services\Messaging\GatewayResponse;
use App\Support\Settings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Taqnyat SMS gateway, talking to the real Taqnyat API directly via
 * Laravel's HTTP client rather than the `taqnyat/php` Composer package.
 *
 * Decision (see task summary for rationale): `taqnyat/php` v1.0.2 is a
 * single global (non-namespaced) `TaqnyatSms` class that hardcodes
 * `CURLOPT_SSL_VERIFYPEER => false` on every request — it disables TLS
 * certificate verification unconditionally, which is unacceptable for a
 * production integration. It also has no PSR interfaces, returns errors
 * as bare strings, and is awkward to unit-test/mock. We instead
 * replicate the exact wire protocol confirmed by reading the package
 * source (github.com/taqnyat/php, `TaqnyatSms.php`):
 *
 *   POST https://api.taqnyat.sa/v1/messages
 *   Header: Authorization: Bearer {TAQNYAT_API_KEY}
 *   Body (JSON): { recipients: string[], sender, body, smsId,
 *                  scheduledDatetime, deleteId }
 *
 * The exact success/error JSON response schema is not documented beyond
 * the package source, so the full decoded body is always kept in
 * GatewayResponse::$raw for auditing, and success/failure is decided
 * primarily from the HTTP status code.
 */
class TaqnyatSmsGateway implements SmsGatewayInterface
{
    public function __construct(
        protected readonly string $apiKey,
        protected readonly string $sender,
        protected readonly string $baseUrl = 'https://api.taqnyat.sa',
    ) {}

    public function send(string $to, string $message): GatewayResponse
    {
        // The API key and sender name are both admin-editable at runtime
        // from the notifications settings screen (App\Support\Settings —
        // the key is encrypted at rest), falling back to the
        // constructor-configured defaults (config('services.taqnyat.*'),
        // i.e. .env) when no override has been saved yet. Read fresh on
        // every send rather than cached on the instance, so a saved
        // credential change is picked up immediately even by a
        // long-running queue worker holding onto this gateway object.
        $settings = app(Settings::class);
        $apiKey = $settings->getSecret('taqnyat_api_key') ?: $this->apiKey;
        $sender = $settings->get('taqnyat_sender') ?: $this->sender;

        $response = Http::withToken($apiKey)
            ->acceptJson()
            ->post(rtrim($this->baseUrl, '/').'/v1/messages', [
                'recipients' => [$to],
                'sender' => $sender,
                'body' => $message,
                'smsId' => (string) Str::uuid(),
                'scheduledDatetime' => '',
                'deleteId' => '',
            ]);

        $raw = (array) ($response->json() ?? []);

        if ($response->status() === 429) {
            return GatewayResponse::failure(
                is_string($raw['message'] ?? null) ? $raw['message'] : 'Taqnyat rate limit exceeded.',
                $raw,
                $this->parseRetryAfter($response->header('Retry-After')),
            );
        }

        if ($response->failed()) {
            $error = is_string($raw['message'] ?? null)
                ? $raw['message']
                : 'Taqnyat request failed with status '.$response->status().'.';

            return GatewayResponse::failure($error, $raw);
        }

        $messageId = $raw['messageId'] ?? ($raw['messages'][0]['id'] ?? null);

        return GatewayResponse::success(is_string($messageId) ? $messageId : null, $raw);
    }

    /**
     * Lightweight credential check: Taqnyat's account balance endpoint.
     *
     * Confirmed by reading github.com/taqnyat/php (`TaqnyatSms::balance()`
     * in TaqnyatSms.php): `GET {base}/account/balance` — note *no* `/v1`
     * prefix, unlike `/v1/messages` — with the same `Authorization: Bearer`
     * header. The response schema isn't documented beyond the package
     * source (no sample JSON in the README either), so we don't assume a
     * specific field name for the balance figure; the whole decoded body
     * is kept in GatewayResponse::$raw for the settings screen / auditing
     * to read whatever the account happens to return.
     */
    public function verify(): GatewayResponse
    {
        $apiKey = app(Settings::class)->getSecret('taqnyat_api_key') ?: $this->apiKey;

        if ($apiKey === '') {
            return GatewayResponse::failure('Taqnyat API key is not configured.');
        }

        $response = Http::withToken($apiKey)
            ->acceptJson()
            ->get(rtrim($this->baseUrl, '/').'/account/balance');

        $raw = (array) ($response->json() ?? []);

        if ($response->failed()) {
            $error = is_string($raw['message'] ?? null)
                ? $raw['message']
                : 'Taqnyat request failed with status '.$response->status().'.';

            return GatewayResponse::failure($error, $raw);
        }

        return GatewayResponse::success(null, $raw);
    }

    protected function parseRetryAfter(?string $header): ?int
    {
        if ($header === null || $header === '') {
            return null;
        }

        if (ctype_digit($header)) {
            return (int) $header;
        }

        $timestamp = strtotime($header);

        return $timestamp === false ? null : max(0, $timestamp - time());
    }
}

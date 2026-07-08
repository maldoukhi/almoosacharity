<?php

use App\Services\Messaging\Drivers\OktaWhatsAppGateway;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response as Psr7Response;
use Okta\Connect\WhatsApp\Config;
use Okta\Connect\WhatsApp\Http\HttpClient as SdkHttpClient;

/**
 * Exercises the real okta-connect-sdk Client + HttpClient stack against a
 * mocked Guzzle handler (no network), confirming our gateway builds the
 * exact payload the vendor source/tests document for messages()->send()
 * and templates()->send().
 */
function makeOktaGateway(array $queue, array &$history = []): OktaWhatsAppGateway
{
    $mock = new MockHandler($queue);
    $stack = HandlerStack::create($mock);
    $stack->push(Middleware::history($history));

    $guzzle = new GuzzleClient(['handler' => $stack, 'http_errors' => false]);

    $config = new Config(
        baseUrl: 'https://connect.example.com',
        token: 'test-token',
        retries: 0,
        httpClient: $guzzle,
    );

    return new OktaWhatsAppGateway(
        baseUrl: 'https://connect.example.com',
        token: 'test-token',
        channelId: 'ch_1',
        httpClient: new SdkHttpClient($config),
    );
}

it('sends a text message to /api/v1/messages with the idempotency header', function () {
    $history = [];
    $gateway = makeOktaGateway([
        new Psr7Response(201, ['Content-Type' => 'application/json'], json_encode([
            'data' => ['id' => 'msg_1', 'channel_id' => 'ch_1', 'type' => 'text', 'status' => 'queued'],
        ])),
    ], $history);

    $response = $gateway->sendText('+966500000000', 'مرحباً', idempotencyKey: 'log-1');

    expect($response->success)->toBeTrue()
        ->and($response->providerMessageId)->toBe('msg_1');

    $request = $history[0]['request'];
    expect($request->getMethod())->toBe('POST')
        ->and($request->getUri()->getPath())->toBe('/api/v1/messages')
        ->and($request->getHeaderLine('Idempotency-Key'))->toBe('log-1')
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer test-token');

    $body = json_decode((string) $request->getBody(), true);
    expect($body)->toBe([
        'channel_id' => 'ch_1',
        // The recipient goes out as wa_id in international form (no +), even
        // though it was passed in the local/plus form; the body is flat.
        'wa_id' => '966500000000',
        'type' => 'text',
        'body' => 'مرحباً',
    ]);
});

it('sends a WhatsApp text to a local 05… number as an international wa_id', function () {
    $history = [];
    $gateway = makeOktaGateway([
        new Psr7Response(201, ['Content-Type' => 'application/json'], json_encode([
            'data' => ['id' => 'msg_local', 'channel_id' => 'ch_1', 'type' => 'text', 'status' => 'queued'],
        ])),
    ], $history);

    $gateway->sendText('0560249160', 'مرحباً');

    $body = json_decode((string) $history[0]['request']->getBody(), true);
    expect($body['wa_id'])->toBe('966560249160');
});

it('sends a template message to /api/v1/templates/send', function () {
    $history = [];
    $gateway = makeOktaGateway([
        new Psr7Response(200, ['Content-Type' => 'application/json'], json_encode([
            'data' => ['id' => 'msg_2', 'channel_id' => 'ch_1', 'type' => 'template', 'status' => 'queued'],
        ])),
    ], $history);

    $response = $gateway->sendTemplate('+966500000000', 'aid_approved', ['أحمد', '500'], 'ar', 'log-2');

    expect($response->success)->toBeTrue()
        ->and($response->providerMessageId)->toBe('msg_2');

    $request = $history[0]['request'];
    expect($request->getUri()->getPath())->toBe('/api/v1/templates/send')
        ->and($request->getHeaderLine('Idempotency-Key'))->toBe('log-2');

    $body = json_decode((string) $request->getBody(), true);
    expect($body)->toBe([
        'channel_id' => 'ch_1',
        'wa_id' => '966500000000',
        'template_name' => 'aid_approved',
        'language' => 'ar',
        'variables' => ['أحمد', '500'],
    ]);
});

it('surfaces RateLimitException::retryAfter() as GatewayResponse::retryAfter', function () {
    $history = [];
    $gateway = makeOktaGateway([
        new Psr7Response(429, ['Retry-After' => '20'], json_encode(['message' => 'Too many requests'])),
    ], $history);

    $response = $gateway->sendText('+966500000000', 'مرحباً');

    expect($response->success)->toBeFalse()
        ->and($response->retryAfter)->toBe(20);
});

it('verifies the connection by fetching the configured channel', function () {
    $history = [];
    $gateway = makeOktaGateway([
        new Psr7Response(200, ['Content-Type' => 'application/json'], json_encode([
            'data' => ['id' => 'ch_1', 'display_name' => 'Main', 'status' => 'connected'],
        ])),
    ], $history);

    $response = $gateway->verify();

    expect($response->success)->toBeTrue()
        ->and($response->providerMessageId)->toBe('ch_1')
        ->and($response->raw)->toMatchArray(['status' => 'connected']);

    $request = $history[0]['request'];
    expect($request->getMethod())->toBe('GET')
        ->and($request->getUri()->getPath())->toBe('/api/v1/channels/ch_1');
});

it('maps a 401 on verify() to a failure via the typed AuthenticationException', function () {
    $history = [];
    $gateway = makeOktaGateway([
        new Psr7Response(401, [], json_encode(['message' => 'Invalid token'])),
    ], $history);

    $response = $gateway->verify();

    expect($response->success)->toBeFalse()
        ->and($response->error)->toBe('Invalid token');
});

it('starts a QR pairing session against /api/integrations/qr/sessions', function () {
    $history = [];
    $gateway = makeOktaGateway([
        new Psr7Response(201, ['Content-Type' => 'application/json'], json_encode([
            'channel' => ['id' => 'ch_new', 'display_name' => 'Almoosa WhatsApp', 'status' => 'pending'],
            'qr' => 'RAW-QR-PAYLOAD',
            'qr_ttl_seconds' => 60,
        ])),
    ], $history);

    $session = $gateway->startQrPairing('Almoosa WhatsApp');

    expect($session->channelId)->toBe('ch_new')
        ->and($session->status)->toBe('pending')
        ->and($session->qr)->toBe('RAW-QR-PAYLOAD')
        ->and($session->qrTtlSeconds)->toBe(60)
        ->and($session->isConnected())->toBeFalse()
        ->and($session->isTerminal())->toBeFalse();

    $request = $history[0]['request'];
    expect($request->getMethod())->toBe('POST')
        ->and($request->getUri()->getPath())->toBe('/api/integrations/qr/sessions');

    $body = json_decode((string) $request->getBody(), true);
    expect($body)->toBe(['display_name' => 'Almoosa WhatsApp']);
});

it('polls QR pairing status until the channel is connected', function () {
    $history = [];
    $gateway = makeOktaGateway([
        new Psr7Response(200, ['Content-Type' => 'application/json'], json_encode([
            'channel' => ['id' => 'ch_new', 'display_name' => 'Almoosa WhatsApp', 'status' => 'connected'],
            'qr' => null,
            'qr_ttl_seconds' => null,
        ])),
    ], $history);

    $session = $gateway->qrPairingStatus('ch_new');

    expect($session->status)->toBe('connected')
        ->and($session->qr)->toBeNull()
        ->and($session->isConnected())->toBeTrue()
        ->and($session->isTerminal())->toBeTrue();

    $request = $history[0]['request'];
    expect($request->getMethod())->toBe('GET')
        ->and($request->getUri()->getPath())->toBe('/api/integrations/qr/sessions/ch_new');
});

it('wraps a failed QR pairing call in a plain RuntimeException, not a vendor exception', function () {
    $history = [];
    $gateway = makeOktaGateway([
        new Psr7Response(404, [], json_encode(['message' => 'Session not found'])),
    ], $history);

    expect(fn () => $gateway->qrPairingStatus('missing'))
        ->toThrow(RuntimeException::class, 'Session not found');
});

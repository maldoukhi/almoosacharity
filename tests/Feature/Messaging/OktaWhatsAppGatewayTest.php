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
        'to' => '+966500000000',
        'type' => 'text',
        'text' => ['body' => 'مرحباً'],
    ]);
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
        'wa_id' => '+966500000000',
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

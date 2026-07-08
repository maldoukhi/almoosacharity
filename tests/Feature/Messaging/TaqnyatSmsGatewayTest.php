<?php

use App\Services\Messaging\Drivers\TaqnyatSmsGateway;
use App\Support\Settings;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

it('sends the exact request shape the real Taqnyat API expects, with the recipient in international format', function () {
    Http::fake([
        'api.taqnyat.sa/*' => Http::response(['messageId' => 'msg-123', 'accepted' => ['966500000000']], 200),
    ]);

    $gateway = new TaqnyatSmsGateway(apiKey: 'test-bearer-token', sender: 'ALMOOSA');

    // Local 05… form on the way in — Taqnyat rejects it, so the gateway
    // must convert it to 9665… before sending.
    $response = $gateway->send('0500000000', 'رسالة اختبار');

    expect($response->success)->toBeTrue()
        ->and($response->providerMessageId)->toBe('msg-123')
        ->and($response->raw)->toMatchArray(['messageId' => 'msg-123']);

    Http::assertSent(function (Request $request): bool {
        return $request->url() === 'https://api.taqnyat.sa/v1/messages'
            && $request->method() === 'POST'
            && $request->hasHeader('Authorization', 'Bearer test-bearer-token')
            && $request['recipients'] === ['966500000000']
            && $request['sender'] === 'ALMOOSA'
            && $request['body'] === 'رسالة اختبار'
            && is_string($request['smsId']) && $request['smsId'] !== '';
    });
});

it('normalizes assorted local/international recipient inputs to the 9665XXXXXXXX form', function () {
    Http::fake([
        'api.taqnyat.sa/*' => Http::response(['messageId' => 'msg-1'], 200),
    ]);

    $gateway = new TaqnyatSmsGateway(apiKey: 'test-bearer-token', sender: 'ALMOOSA');

    foreach (['0500000000', '+966500000000', '00966500000000', '966500000000'] as $input) {
        $gateway->send($input, 'x');
    }

    Http::assertSent(fn (Request $request): bool => $request['recipients'] === ['966500000000']);
});

it('maps a non-2xx response to a failed GatewayResponse with the error message', function () {
    Http::fake([
        'api.taqnyat.sa/*' => Http::response(['message' => 'Invalid sender name.'], 400),
    ]);

    $gateway = new TaqnyatSmsGateway(apiKey: 'test-bearer-token', sender: 'BADSENDER');

    $response = $gateway->send('+966500000000', 'رسالة اختبار');

    expect($response->success)->toBeFalse()
        ->and($response->error)->toBe('Invalid sender name.')
        ->and($response->retryAfter)->toBeNull();
});

it('captures the Retry-After header as retryAfter on a 429 response', function () {
    Http::fake([
        'api.taqnyat.sa/*' => Http::response(['message' => 'Too many requests.'], 429, ['Retry-After' => '45']),
    ]);

    $gateway = new TaqnyatSmsGateway(apiKey: 'test-bearer-token', sender: 'ALMOOSA');

    $response = $gateway->send('+966500000000', 'رسالة اختبار');

    expect($response->success)->toBeFalse()
        ->and($response->retryAfter)->toBe(45);
});

it('verifies the connection against the account balance endpoint (no /v1 prefix)', function () {
    Http::fake([
        'api.taqnyat.sa/account/balance' => Http::response(['balance' => '42.50'], 200),
    ]);

    $gateway = new TaqnyatSmsGateway(apiKey: 'test-bearer-token', sender: 'ALMOOSA');

    $response = $gateway->verify();

    expect($response->success)->toBeTrue()
        ->and($response->raw)->toMatchArray(['balance' => '42.50']);

    Http::assertSent(function (Request $request): bool {
        return $request->url() === 'https://api.taqnyat.sa/account/balance'
            && $request->method() === 'GET'
            && $request->hasHeader('Authorization', 'Bearer test-bearer-token');
    });
});

it('maps a failed verify() response to a failure with the provider message', function () {
    Http::fake([
        'api.taqnyat.sa/account/balance' => Http::response(['message' => 'Invalid token.'], 401),
    ]);

    $gateway = new TaqnyatSmsGateway(apiKey: 'bad-token', sender: 'ALMOOSA');

    $response = $gateway->verify();

    expect($response->success)->toBeFalse()
        ->and($response->error)->toBe('Invalid token.');
});

it('short-circuits verify() without an HTTP call when no API key is configured', function () {
    Http::fake();

    $gateway = new TaqnyatSmsGateway(apiKey: '', sender: 'ALMOOSA');

    $response = $gateway->verify();

    expect($response->success)->toBeFalse();
    Http::assertNothingSent();
});

it('prefers a Settings-stored API key over the constructor default for verify()', function () {
    Http::fake([
        'api.taqnyat.sa/account/balance' => Http::response(['balance' => '10.00'], 200),
    ]);

    app(Settings::class)->setSecret('taqnyat_api_key', 'settings-token');

    $gateway = new TaqnyatSmsGateway(apiKey: 'constructor-token', sender: 'ALMOOSA');
    $gateway->verify();

    Http::assertSent(fn (Request $request): bool => $request->hasHeader('Authorization', 'Bearer settings-token'));
});

it('lists accepted sender names from the v1/messages/senders endpoint', function () {
    Http::fake([
        'api.taqnyat.sa/v1/messages/senders' => Http::response([
            'senders' => [
                ['name' => 'ALMOOSA', 'status' => 'accepted'],
                ['name' => 'PENDING1', 'status' => 'pending'],
            ],
        ], 200),
    ]);

    $gateway = new TaqnyatSmsGateway(apiKey: 'test-bearer-token', sender: 'ALMOOSA');

    $response = $gateway->senders();

    expect($response->success)->toBeTrue()
        ->and($response->raw['normalizedSenders'])->toBe([
            ['name' => 'ALMOOSA', 'status' => 'accepted'],
        ]);

    Http::assertSent(function (Request $request): bool {
        return $request->url() === 'https://api.taqnyat.sa/v1/messages/senders'
            && $request->method() === 'GET'
            && $request->hasHeader('Authorization', 'Bearer test-bearer-token');
    });
});

it('tolerates a bare-string sender list and a root-level array with no wrapper key', function () {
    Http::fake([
        'api.taqnyat.sa/v1/messages/senders' => Http::response(['ALMOOSA', 'CHARITY'], 200),
    ]);

    $gateway = new TaqnyatSmsGateway(apiKey: 'test-bearer-token', sender: 'ALMOOSA');

    $response = $gateway->senders();

    expect($response->raw['normalizedSenders'])->toBe([
        ['name' => 'ALMOOSA', 'status' => null],
        ['name' => 'CHARITY', 'status' => null],
    ]);
});

it('tolerates alternate field names for the sender list wrapper, name, and status', function () {
    Http::fake([
        'api.taqnyat.sa/v1/messages/senders' => Http::response([
            'data' => [
                ['senderName' => 'ALTNAME', 'state' => 'APPROVED'],
                ['sender_name' => 'REJECTEDNAME', 'approval_status' => 'rejected'],
            ],
        ], 200),
    ]);

    $gateway = new TaqnyatSmsGateway(apiKey: 'test-bearer-token', sender: 'ALMOOSA');

    $response = $gateway->senders();

    expect($response->raw['normalizedSenders'])->toBe([
        ['name' => 'ALTNAME', 'status' => 'accepted'],
    ]);
});

it('maps a failed senders() response to a failure with the provider message', function () {
    Http::fake([
        'api.taqnyat.sa/v1/messages/senders' => Http::response(['message' => 'Invalid token.'], 401),
    ]);

    $gateway = new TaqnyatSmsGateway(apiKey: 'bad-token', sender: 'ALMOOSA');

    $response = $gateway->senders();

    expect($response->success)->toBeFalse()
        ->and($response->error)->toBe('Invalid token.');
});

it('short-circuits senders() without an HTTP call when no API key is configured', function () {
    Http::fake();

    $gateway = new TaqnyatSmsGateway(apiKey: '', sender: 'ALMOOSA');

    $response = $gateway->senders();

    expect($response->success)->toBeFalse();
    Http::assertNothingSent();
});

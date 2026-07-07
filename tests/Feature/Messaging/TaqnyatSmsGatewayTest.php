<?php

use App\Services\Messaging\Drivers\TaqnyatSmsGateway;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

it('sends the exact request shape the real Taqnyat API expects', function () {
    Http::fake([
        'api.taqnyat.sa/*' => Http::response(['messageId' => 'msg-123', 'accepted' => ['+966500000000']], 200),
    ]);

    $gateway = new TaqnyatSmsGateway(apiKey: 'test-bearer-token', sender: 'ALMOOSA');

    $response = $gateway->send('+966500000000', 'رسالة اختبار');

    expect($response->success)->toBeTrue()
        ->and($response->providerMessageId)->toBe('msg-123')
        ->and($response->raw)->toMatchArray(['messageId' => 'msg-123']);

    Http::assertSent(function (Request $request): bool {
        return $request->url() === 'https://api.taqnyat.sa/v1/messages'
            && $request->method() === 'POST'
            && $request->hasHeader('Authorization', 'Bearer test-bearer-token')
            && $request['recipients'] === ['+966500000000']
            && $request['sender'] === 'ALMOOSA'
            && $request['body'] === 'رسالة اختبار'
            && is_string($request['smsId']) && $request['smsId'] !== '';
    });
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

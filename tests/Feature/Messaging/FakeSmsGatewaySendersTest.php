<?php

use App\Services\Messaging\Drivers\FakeSmsGateway;

beforeEach(function () {
    FakeSmsGateway::reset();
});

it('returns a fixed fake sender list', function () {
    $response = (new FakeSmsGateway)->senders();

    expect($response->success)->toBeTrue()
        ->and($response->raw['normalizedSenders'])->toBe([
            ['name' => 'Almoosa', 'status' => 'accepted'],
            ['name' => 'Charity', 'status' => 'accepted'],
        ]);
});

it('fails senders() the same way as send()/verify() once forced to fail', function () {
    FakeSmsGateway::failNext('Fake gateway down.');

    $response = (new FakeSmsGateway)->senders();

    expect($response->success)->toBeFalse()
        ->and($response->error)->toBe('Fake gateway down.');
});

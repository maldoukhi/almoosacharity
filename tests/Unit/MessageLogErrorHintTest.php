<?php

use App\Models\MessageLog;

it('maps a recognized provider error to a friendly, actionable hint', function (string $rawError, string $expectedKey) {
    $log = new MessageLog(['error' => $rawError]);

    expect($log->errorHint())->toBe(__($expectedKey));
})->with([
    'invalid credentials' => ['invalid credentials information', 'reports.messages.hint_invalid_credentials'],
    'unauthorized' => ['401 Unauthorized', 'reports.messages.hint_invalid_credentials'],
    'balance' => ['not enough balance', 'reports.messages.hint_insufficient_balance'],
    'sender' => ['Invalid sender name.', 'reports.messages.hint_invalid_sender'],
    'rate limit' => ['Too many requests.', 'reports.messages.hint_rate_limited'],
    'recipient' => ['The wa id / mobile number is invalid', 'reports.messages.hint_invalid_recipient'],
]);

it('returns null for an unrecognized or empty error', function () {
    expect((new MessageLog(['error' => 'some totally unknown gateway hiccup']))->errorHint())->toBeNull()
        ->and((new MessageLog(['error' => null]))->errorHint())->toBeNull();
});

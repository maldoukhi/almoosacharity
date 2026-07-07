<?php

use App\Enums\MessageChannel;
use App\Enums\MessageStatus;
use App\Jobs\Messaging\SendSmsMessage;
use App\Models\MessageLog;
use App\Services\Messaging\Contracts\SmsGatewayInterface;
use App\Services\Messaging\Drivers\FakeSmsGateway;
use App\Services\Messaging\GatewayResponse;

beforeEach(function () {
    FakeSmsGateway::reset();
});

it('marks the message_log as sent with a provider_message_id on success', function () {
    $log = MessageLog::factory()->create([
        'channel' => MessageChannel::Sms,
        'status' => MessageStatus::Pending,
        'recipient' => '+966500000000',
        'body' => 'مرحباً بك',
        'attempts' => 0,
    ]);

    (new SendSmsMessage($log->id))->handle(app(SmsGatewayInterface::class));

    $log->refresh();

    expect($log->status)->toBe(MessageStatus::Sent)
        ->and($log->provider_message_id)->toStartWith('fake-sms-')
        ->and($log->error)->toBeNull()
        ->and($log->attempts)->toBe(1)
        ->and($log->sent_at)->not->toBeNull();

    expect(FakeSmsGateway::$sent)->toHaveCount(1)
        ->and(FakeSmsGateway::$sent[0]['to'])->toBe('+966500000000');
});

it('retries on failure and marks the log failed with the error once attempts are exhausted', function () {
    FakeSmsGateway::failNext('Provider rejected the message.');

    $log = MessageLog::factory()->create([
        'channel' => MessageChannel::Sms,
        'status' => MessageStatus::Pending,
        'attempts' => 0,
    ]);

    $job = new SendSmsMessage($log->id);

    // Attempt 1/3: still under the retry budget, so handle() throws to let
    // the queue worker retry after the next backoff step.
    expect(fn () => $job->handle(app(SmsGatewayInterface::class)))
        ->toThrow(RuntimeException::class, 'Provider rejected the message.');

    $log->refresh();
    expect($log->attempts)->toBe(1)
        ->and($log->status)->toBe(MessageStatus::Pending)
        ->and($log->error)->toBe('Provider rejected the message.');

    // Attempt 2/3: same outcome.
    expect(fn () => $job->handle(app(SmsGatewayInterface::class)))->toThrow(RuntimeException::class);
    expect($log->fresh()->attempts)->toBe(2);

    // Attempt 3/3: budget exhausted, the job gives up quietly and marks
    // the log as failed instead of throwing again.
    $job->handle(app(SmsGatewayInterface::class));

    $log->refresh();
    expect($log->status)->toBe(MessageStatus::Failed)
        ->and($log->attempts)->toBe(3)
        ->and($log->error)->toBe('Provider rejected the message.');
});

it('releases the job instead of failing when the gateway reports a retry-after window', function () {
    FakeSmsGateway::reset();

    $log = MessageLog::factory()->create([
        'channel' => MessageChannel::Sms,
        'status' => MessageStatus::Pending,
        'attempts' => 0,
    ]);

    $job = Mockery::mock(SendSmsMessage::class, [$log->id])->makePartial();
    $job->shouldAllowMockingProtectedMethods();
    $job->shouldReceive('release')->once()->with(30);

    $gateway = Mockery::mock(SmsGatewayInterface::class);
    $gateway->shouldReceive('send')->once()->andReturn(
        GatewayResponse::failure('Rate limited.', [], retryAfter: 30)
    );

    $job->handle($gateway);

    $log->refresh();
    expect($log->status)->toBe(MessageStatus::Pending)
        ->and($log->error)->toBe('Rate limited.')
        ->and($log->attempts)->toBe(1);
});

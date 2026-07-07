<?php

use App\Enums\MessageChannel;
use App\Enums\MessageStatus;
use App\Jobs\Messaging\SendWhatsAppMessage;
use App\Models\MessageLog;
use App\Services\Messaging\Contracts\WhatsAppGatewayInterface;
use App\Services\Messaging\Drivers\FakeWhatsAppGateway;

beforeEach(function () {
    FakeWhatsAppGateway::reset();
});

it('marks the message_log as sent for a template send', function () {
    $log = MessageLog::factory()->create([
        'channel' => MessageChannel::WhatsApp,
        'status' => MessageStatus::Pending,
        'recipient' => '+966500000000',
        'template_name' => 'aid_approved',
        'attempts' => 0,
    ]);

    $job = new SendWhatsAppMessage(
        messageLogId: $log->id,
        to: '+966500000000',
        templateName: 'aid_approved',
        variables: ['أحمد', '500 ريال'],
        language: 'ar',
        idempotencyKey: 'aid-1-approved',
    );

    $job->handle(app(WhatsAppGatewayInterface::class));

    $log->refresh();
    expect($log->status)->toBe(MessageStatus::Sent)
        ->and($log->provider_message_id)->toStartWith('fake-wa-')
        ->and($log->sent_at)->not->toBeNull();

    expect(FakeWhatsAppGateway::$sent)->toHaveCount(1)
        ->and(FakeWhatsAppGateway::$sent[0]['type'])->toBe('template')
        ->and(FakeWhatsAppGateway::$sent[0]['idempotency_key'])->toBe('aid-1-approved');
});

it('marks the message_log as failed with the error once retries are exhausted', function () {
    FakeWhatsAppGateway::failNext('Channel not connected.');

    $log = MessageLog::factory()->create([
        'channel' => MessageChannel::WhatsApp,
        'status' => MessageStatus::Pending,
        'attempts' => 0,
    ]);

    $job = new SendWhatsAppMessage(messageLogId: $log->id, to: '+966500000000', body: 'مرحباً');

    expect(fn () => $job->handle(app(WhatsAppGatewayInterface::class)))->toThrow(RuntimeException::class);
    expect(fn () => $job->handle(app(WhatsAppGatewayInterface::class)))->toThrow(RuntimeException::class);

    $job->handle(app(WhatsAppGatewayInterface::class));

    $log->refresh();
    expect($log->status)->toBe(MessageStatus::Failed)
        ->and($log->attempts)->toBe(3)
        ->and($log->error)->toBe('Channel not connected.');
});

<?php

use App\Enums\MessageChannel;
use App\Enums\MessageStatus;
use App\Jobs\Messaging\SendSmsMessage;
use App\Jobs\Messaging\SendWhatsAppMessage;
use App\Models\MessageLog;
use App\Models\User;
use App\Services\Messaging\Messenger;
use Illuminate\Support\Facades\Queue;

it('creates a pending sms message_log and dispatches SendSmsMessage', function () {
    Queue::fake();

    $log = app(Messenger::class)->sms('+966500000000', 'مرحباً بك');

    expect($log)->toBeInstanceOf(MessageLog::class)
        ->and($log->channel)->toBe(MessageChannel::Sms)
        ->and($log->status)->toBe(MessageStatus::Pending)
        ->and($log->recipient)->toBe('+966500000000')
        ->and($log->body)->toBe('مرحباً بك')
        ->and($log->attempts)->toBe(0);

    $this->assertDatabaseHas('message_logs', [
        'id' => $log->id,
        'channel' => MessageChannel::Sms->value,
        'status' => MessageStatus::Pending->value,
    ]);

    Queue::assertPushed(SendSmsMessage::class, fn (SendSmsMessage $job): bool => $job->messageLogId === $log->id);
});

it('links the message_log to a related model via the morph relation', function () {
    Queue::fake();

    $user = User::factory()->create();

    $log = app(Messenger::class)->sms('+966500000000', 'مرحباً', related: $user);

    expect($log->messageable_type)->toBe($user->getMorphClass())
        ->and($log->messageable_id)->toBe($user->id);
});

it('creates a pending whatsapp template message_log and dispatches SendWhatsAppMessage', function () {
    Queue::fake();

    $log = app(Messenger::class)->whatsappTemplate(
        to: '+966500000000',
        templateName: 'aid_approved',
        variables: ['أحمد', '500 ريال'],
    );

    expect($log->channel)->toBe(MessageChannel::WhatsApp)
        ->and($log->status)->toBe(MessageStatus::Pending)
        ->and($log->template_name)->toBe('aid_approved');

    Queue::assertPushed(SendWhatsAppMessage::class, function (SendWhatsAppMessage $job) use ($log): bool {
        return $job->messageLogId === $log->id
            && $job->templateName === 'aid_approved'
            && $job->variables === ['أحمد', '500 ريال']
            && $job->idempotencyKey === 'message-log-'.$log->id;
    });
});

it('uses a caller-supplied idempotency key when given', function () {
    Queue::fake();

    $log = app(Messenger::class)->whatsappTemplate(
        to: '+966500000000',
        templateName: 'aid_approved',
        variables: [],
        idempotencyKey: 'aid-42-approved',
    );

    Queue::assertPushed(SendWhatsAppMessage::class, fn (SendWhatsAppMessage $job): bool => $job->idempotencyKey === 'aid-42-approved'
        && $job->messageLogId === $log->id);
});

<?php

use App\Actions\Beneficiaries\CreateBeneficiary;
use App\Actions\Messaging\SendBroadcast;
use App\Enums\BroadcastStatus;
use App\Enums\MessageChannel;
use App\Enums\RoleName;
use App\Jobs\Messaging\SendBroadcastMessages;
use App\Jobs\Messaging\SendSmsMessage;
use App\Models\Broadcast;
use App\Models\MessageLog;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Queue;

/**
 * `beneficiaries.mobile` is a NOT NULL column and always required by the
 * form/action in normal use, but nothing stops a blank string ('') from
 * being persisted when calling the action directly like these tests do —
 * used here to exercise the "no mobile on file" broadcast-exclusion path
 * without violating the NOT NULL constraint.
 */
function createBeneficiaryWithMobile(?string $mobile, array $overrides = [])
{
    $actor = asDataEntry();

    return app(CreateBeneficiary::class)->handle(
        beneficiaryAttributes(array_merge([
            'national_id' => (string) random_int(1000000000, 1999999999),
            'mobile' => $mobile ?? '',
        ], $overrides)),
        [],
        $actor,
    );
}

it('creates a broadcast row and sends only to beneficiaries with a mobile number, inline when small', function () {
    Queue::fake();

    $withMobile1 = createBeneficiaryWithMobile('0511111111', ['first_name' => 'سارة', 'second_name' => '']);
    $withMobile2 = createBeneficiaryWithMobile('0522222222', ['first_name' => 'منى', 'second_name' => '']);
    $withoutMobile = createBeneficiaryWithMobile(null, ['first_name' => 'بلا']);

    $actor = asManager();

    $broadcast = app(SendBroadcast::class)->handle(
        beneficiaryIds: [$withMobile1->id, $withMobile2->id, $withoutMobile->id],
        channel: 'sms',
        body: 'مرحبًا {name}، هذه رسالة تجريبية.',
        templateName: null,
        actor: $actor,
    );

    expect($broadcast)->toBeInstanceOf(Broadcast::class)
        ->and($broadcast->channel)->toBe(MessageChannel::Sms)
        ->and($broadcast->recipients_count)->toBe(2)
        ->and($broadcast->status)->toBe(BroadcastStatus::Completed)
        ->and($broadcast->sent_by)->toBe($actor->id);

    // Only the two eligible beneficiaries got an actual send dispatched.
    Queue::assertPushed(SendSmsMessage::class, 2);

    $logs = MessageLog::query()
        ->where('messageable_type', $broadcast->getMorphClass())
        ->where('messageable_id', $broadcast->id)
        ->get();

    expect($logs)->toHaveCount(2);
    expect($logs->pluck('body')->contains("مرحبًا {$withMobile1->full_name}، هذه رسالة تجريبية."))->toBeTrue();
    expect($logs->pluck('body')->contains("مرحبًا {$withMobile2->full_name}، هذه رسالة تجريبية."))->toBeTrue();

    // The threshold path (SendBroadcastMessages queued as its own job) is
    // not exercised for a batch this small — it ran inline instead.
    Queue::assertNotPushed(SendBroadcastMessages::class);
});

it('queues a SendBroadcastMessages job instead of sending inline once eligible recipients exceed the inline threshold', function () {
    Queue::fake();

    $actor = asManager();
    $ids = [];

    // 201 eligible beneficiaries: one over the 200 inline threshold.
    for ($i = 0; $i < 201; $i++) {
        $ids[] = createBeneficiaryWithMobile('05'.str_pad((string) $i, 8, '0', STR_PAD_LEFT))->id;
    }

    $broadcast = app(SendBroadcast::class)->handle(
        beneficiaryIds: $ids,
        channel: 'sms',
        body: 'مرحبًا {name}',
        templateName: null,
        actor: $actor,
    );

    expect($broadcast->recipients_count)->toBe(201)
        ->and($broadcast->status)->toBe(BroadcastStatus::Queued);

    Queue::assertPushed(SendBroadcastMessages::class, fn (SendBroadcastMessages $job): bool => $job->broadcastId === $broadcast->id
        && count($job->beneficiaryIds) === 201);

    // Nothing was actually sent yet: that only happens once the queued
    // job itself runs.
    Queue::assertNotPushed(SendSmsMessage::class);
});

it('rejects a user without messages.broadcast', function () {
    $beneficiary = createBeneficiaryWithMobile('0511111111');

    $actor = asResearcher();

    expect(fn () => app(SendBroadcast::class)->handle(
        beneficiaryIds: [$beneficiary->id],
        channel: 'sms',
        body: 'مرحبًا {name}',
        templateName: null,
        actor: $actor,
    ))->toThrow(AuthorizationException::class);
});

it('grants messages.broadcast to manager and system-admin but not researcher or data-entry', function () {
    seedRolesAndPermissions();

    expect(userWithRole(RoleName::Manager)->can('messages.broadcast'))->toBeTrue();
    expect(userWithRole(RoleName::SystemAdmin)->can('messages.broadcast'))->toBeTrue();
    expect(userWithRole(RoleName::SocialResearcher)->can('messages.broadcast'))->toBeFalse();
    expect(userWithRole(RoleName::DataEntry)->can('messages.broadcast'))->toBeFalse();
});

it('sends to manual numbers with no beneficiary, using the neutral default recipient name', function () {
    Queue::fake();

    $actor = asManager();

    $broadcast = app(SendBroadcast::class)->handle(
        beneficiaryIds: [],
        channel: 'sms',
        body: 'مرحبًا {name}، رسالة تجريبية.',
        templateName: null,
        actor: $actor,
        manualNumbers: ['0533333333', '0544444444'],
    );

    expect($broadcast->recipients_count)->toBe(2)
        ->and($broadcast->manual_numbers_count)->toBe(2)
        ->and($broadcast->status)->toBe(BroadcastStatus::Completed);

    Queue::assertPushed(SendSmsMessage::class, 2);

    $logs = MessageLog::query()
        ->where('messageable_type', $broadcast->getMorphClass())
        ->where('messageable_id', $broadcast->id)
        ->get();

    expect($logs)->toHaveCount(2);

    $expectedBody = 'مرحبًا '.__('messaging.default_recipient_name').'، رسالة تجريبية.';
    expect($logs->pluck('body')->every(fn (string $body): bool => $body === $expectedBody))->toBeTrue();
    expect($logs->pluck('recipient')->all())->toEqualCanonicalizing(['0533333333', '0544444444']);
});

it('drops a manual number that duplicates a selected beneficiary mobile so it is only sent once', function () {
    Queue::fake();

    $beneficiary = createBeneficiaryWithMobile('0511111111');
    $actor = asManager();

    $broadcast = app(SendBroadcast::class)->handle(
        beneficiaryIds: [$beneficiary->id],
        channel: 'sms',
        body: 'مرحبًا {name}',
        templateName: null,
        actor: $actor,
        manualNumbers: ['0511111111', '0522222222'],
    );

    expect($broadcast->recipients_count)->toBe(2) // beneficiary + only the non-duplicate manual number
        ->and($broadcast->manual_numbers_count)->toBe(1);

    Queue::assertPushed(SendSmsMessage::class, 2);

    $logs = MessageLog::query()
        ->where('messageable_type', $broadcast->getMorphClass())
        ->where('messageable_id', $broadcast->id)
        ->get();

    expect($logs)->toHaveCount(2);
    expect($logs->pluck('recipient')->all())->toEqualCanonicalizing(['0511111111', '0522222222']);
});

it('normalizes an international-format manual number before de-duplicating and sending', function () {
    Queue::fake();

    $actor = asManager();

    $broadcast = app(SendBroadcast::class)->handle(
        beneficiaryIds: [],
        channel: 'sms',
        body: 'مرحبًا {name}',
        templateName: null,
        actor: $actor,
        manualNumbers: ['+966533333333', '0533333333'], // same number, two formats
    );

    expect($broadcast->recipients_count)->toBe(1)
        ->and($broadcast->manual_numbers_count)->toBe(1);

    $log = MessageLog::query()
        ->where('messageable_type', $broadcast->getMorphClass())
        ->where('messageable_id', $broadcast->id)
        ->sole();

    expect($log->recipient)->toBe('0533333333');
});

it('queues manual numbers through SendBroadcastMessages once the total exceeds the inline threshold', function () {
    Queue::fake();

    $actor = asManager();
    $manualNumbers = [];

    for ($i = 0; $i < 201; $i++) {
        $manualNumbers[] = '05'.str_pad((string) $i, 8, '0', STR_PAD_LEFT);
    }

    $broadcast = app(SendBroadcast::class)->handle(
        beneficiaryIds: [],
        channel: 'sms',
        body: 'مرحبًا {name}',
        templateName: null,
        actor: $actor,
        manualNumbers: $manualNumbers,
    );

    expect($broadcast->recipients_count)->toBe(201)
        ->and($broadcast->status)->toBe(BroadcastStatus::Queued);

    Queue::assertPushed(SendBroadcastMessages::class, fn (SendBroadcastMessages $job): bool => $job->broadcastId === $broadcast->id
        && $job->beneficiaryIds === []
        && count($job->manualNumbers) === 201);

    Queue::assertNotPushed(SendSmsMessage::class);
});

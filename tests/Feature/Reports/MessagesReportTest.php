<?php

use App\Enums\MessageChannel;
use App\Enums\MessageStatus;
use App\Jobs\Messaging\SendSmsMessage;
use App\Models\Aid;
use App\Models\Beneficiary;
use App\Models\Broadcast;
use App\Models\MessageLog;
use App\Models\User;
use App\Reports\Filters\MessagesReportFilter;
use App\Reports\MessagesReport;
use Illuminate\Support\Facades\Queue;

it('shows the broadcast sender\'s name for a broadcast message and "System" for an automated notification', function () {
    $sender = User::factory()->create(['name' => 'محمد الأحمد']);
    $broadcast = Broadcast::factory()->create(['sent_by' => $sender->id]);

    MessageLog::factory()->create([
        'messageable_type' => Broadcast::class,
        'messageable_id' => $broadcast->id,
    ]);

    // An automated aid-lifecycle notification has no human sender — the
    // messageable is an Aid (or another non-Broadcast model), never a
    // Broadcast, so the report must fall back to the system label.
    MessageLog::factory()->create([
        'messageable_type' => Aid::class,
        'messageable_id' => 999999,
    ]);

    $report = new MessagesReport(new MessagesReportFilter);
    $rows = $report->query()->get();

    $broadcastRow = $rows->firstWhere('messageable_type', Broadcast::class);
    $notificationRow = $rows->firstWhere('messageable_type', Aid::class);

    expect($report->senderName($broadcastRow))->toBe('محمد الأحمد');
    expect($report->senderName($notificationRow))->toBe(__('reports.messages.system_sender'));

    expect($report->sourceLabel($broadcastRow))->toBe(__('reports.messages.source_broadcast'));
    expect($report->sourceLabel($notificationRow))->toBe(__('reports.messages.source_notification'));
});

it('matches the recipient number to a beneficiary name via mobile, without matching unrelated numbers', function () {
    asManager();

    $beneficiary = Beneficiary::factory()->create(['mobile' => '0512345678']);

    $matched = MessageLog::factory()->create(['recipient' => '0512345678']);
    $unmatched = MessageLog::factory()->create(['recipient' => '0599999999']);

    $report = new MessagesReport(new MessagesReportFilter);

    expect($report->recipientName($matched))->toBe($beneficiary->full_name);
    expect($report->recipientName($unmatched))->toBeNull();
});

it('filters by channel and by status', function () {
    MessageLog::factory()->create(['channel' => MessageChannel::Sms, 'status' => MessageStatus::Sent]);
    MessageLog::factory()->create(['channel' => MessageChannel::WhatsApp, 'status' => MessageStatus::Failed]);
    MessageLog::factory()->create(['channel' => MessageChannel::Sms, 'status' => MessageStatus::Pending]);

    $smsOnly = (new MessagesReport(new MessagesReportFilter(channel: 'sms')))->query()->get();
    expect($smsOnly)->toHaveCount(2);
    expect($smsOnly->every(fn (MessageLog $log): bool => $log->channel === MessageChannel::Sms))->toBeTrue();

    $failedOnly = (new MessagesReport(new MessagesReportFilter(status: 'failed')))->query()->get();
    expect($failedOnly)->toHaveCount(1);
    expect($failedOnly->first()->status)->toBe(MessageStatus::Failed);

    $totals = (new MessagesReport(new MessagesReportFilter))->totals();
    expect($totals['count'])->toBe(3);
    expect($totals['sent'])->toBe(1);
    expect($totals['failed'])->toBe(1);
    expect($totals['pending'])->toBe(1);
    expect($totals['sms'])->toBe(2);
    expect($totals['whatsapp'])->toBe(1);
});

it('filters by source (broadcast vs automated notification)', function () {
    $broadcast = Broadcast::factory()->create();

    MessageLog::factory()->create(['messageable_type' => Broadcast::class, 'messageable_id' => $broadcast->id]);
    MessageLog::factory()->create(['messageable_type' => Aid::class, 'messageable_id' => 1]);
    MessageLog::factory()->create(['messageable_type' => null, 'messageable_id' => null]);

    $broadcastOnly = (new MessagesReport(new MessagesReportFilter(source: 'broadcast')))->query()->get();
    expect($broadcastOnly)->toHaveCount(1);

    $notificationsOnly = (new MessagesReport(new MessagesReportFilter(source: 'notification')))->query()->get();
    expect($notificationsOnly)->toHaveCount(2);
});

it('re-queues a failed message and resets it to pending', function () {
    Queue::fake();

    asAdmin();

    $failed = MessageLog::factory()->create([
        'channel' => MessageChannel::Sms,
        'status' => MessageStatus::Failed,
        'recipient' => '0512345678',
        'error' => 'invalid credentials information',
    ]);

    Livewire\Livewire::test(App\Livewire\Reports\MessagesReport::class)
        ->call('resend', $failed->id)
        ->assertDispatched('toast');

    expect($failed->fresh()->status)->toBe(MessageStatus::Pending)
        ->and($failed->fresh()->error)->toBeNull();

    Queue::assertPushed(SendSmsMessage::class);
});

it('refuses to resend a message that did not fail', function () {
    asAdmin();

    $sent = MessageLog::factory()->create(['status' => MessageStatus::Sent]);

    Livewire\Livewire::test(App\Livewire\Reports\MessagesReport::class)
        ->call('resend', $sent->id)
        ->assertStatus(404);
});

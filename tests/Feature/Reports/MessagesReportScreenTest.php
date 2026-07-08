<?php

use App\Enums\MessageChannel;
use App\Enums\MessageStatus;
use App\Livewire\Reports\MessagesReport;
use App\Models\MessageLog;
use Livewire\Livewire;

it('narrows the preview table down to the selected channel', function () {
    asManager();

    MessageLog::factory()->create(['recipient' => '0511111111', 'channel' => MessageChannel::Sms]);
    MessageLog::factory()->create(['recipient' => '0522222222', 'channel' => MessageChannel::WhatsApp]);

    Livewire::test(MessagesReport::class)
        ->assertSee('0511111111')
        ->assertSee('0522222222')
        ->set('channel', 'whatsapp')
        ->assertDontSee('0511111111')
        ->assertSee('0522222222');
});

it('narrows the preview table down to the selected status', function () {
    asManager();

    MessageLog::factory()->create(['recipient' => '0533333333', 'status' => MessageStatus::Sent]);
    MessageLog::factory()->create(['recipient' => '0544444444', 'status' => MessageStatus::Failed]);

    Livewire::test(MessagesReport::class)
        ->set('status', 'failed')
        ->assertDontSee('0533333333')
        ->assertSee('0544444444');
});

it('shows the reports index card linking to the messages report', function () {
    asManager();

    $this->get(route('reports.index'))
        ->assertOk()
        ->assertSee(__('reports.index.card_messages_title'));
});

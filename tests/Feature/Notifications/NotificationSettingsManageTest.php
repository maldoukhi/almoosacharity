<?php

use App\Enums\MessageChannel;
use App\Enums\NotificationEvent;
use App\Livewire\Settings\Notifications\Manage;
use App\Models\NotificationTemplate;
use App\Support\Settings;
use Livewire\Livewire;

it('forbids a user without notifications.settings.manage from opening the settings screen', function () {
    asManager();

    Livewire::test(Manage::class)->assertForbidden();
});

it('lets an authorized admin update a template body and the channel toggles', function () {
    (new \Database\Seeders\NotificationTemplateSeeder)->run();
    asAdmin();

    $component = Livewire::test(Manage::class);

    $newBody = 'رسالة محدثة: عزيزي {name}، إعانتك ({program}) بمبلغ {amount} جاهزة.';

    $component
        ->set('templates.'.NotificationEvent::AidApproved->value.'.'.MessageChannel::Sms->value.'.body', $newBody)
        ->set('templates.'.NotificationEvent::AidApproved->value.'.'.MessageChannel::Sms->value.'.is_active', true)
        ->set('smsEnabled', true)
        ->set('whatsappEnabled', true)
        ->set('senderName', 'Almoosa')
        ->call('save')
        ->assertHasNoErrors();

    $template = NotificationTemplate::query()
        ->where('event', NotificationEvent::AidApproved->value)
        ->where('channel', MessageChannel::Sms->value)
        ->first();

    expect($template->body)->toBe($newBody);
    expect($template->is_active)->toBeTrue();

    $settings = app(Settings::class);
    expect($settings->get('sms_enabled'))->toBe('1');
    expect($settings->get('whatsapp_enabled'))->toBe('1');
    expect($settings->get('taqnyat_sender'))->toBe('Almoosa');
});

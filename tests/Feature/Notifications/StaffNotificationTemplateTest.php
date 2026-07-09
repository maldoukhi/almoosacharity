<?php

use App\Enums\MessageChannel;
use App\Enums\NotificationEvent;
use App\Livewire\Settings\Notifications\Manage;
use App\Models\NotificationTemplate;
use Database\Seeders\NotificationTemplateSeeder;
use Livewire\Livewire;

/**
 * Feature 6: the staff (awaiting-approval) templates are editable and
 * persisted from the notifications settings screen, alongside the
 * beneficiary templates.
 */
it('lets an admin edit and persist the staff awaiting-approval email template', function () {
    (new NotificationTemplateSeeder)->run();
    asAdmin();

    $field = 'templates.'.NotificationEvent::AidAwaitingApproval->value.'.'.MessageChannel::Email->value;
    $body = 'إعانة {reference} بانتظار قرارك في مرحلة {stage}. الرابط: {link}';

    Livewire::test(Manage::class)
        ->set($field.'.body', $body)
        ->set($field.'.is_active', true)
        ->call('save')
        ->assertHasNoErrors();

    $template = NotificationTemplate::query()
        ->where('event', NotificationEvent::AidAwaitingApproval->value)
        ->where('channel', MessageChannel::Email->value)
        ->first();

    expect($template)->not->toBeNull();
    expect($template->body)->toBe($body);
    expect($template->is_active)->toBeTrue();
});

it('seeds a staff awaiting-approval template for email and whatsapp only', function () {
    (new NotificationTemplateSeeder)->run();

    $channels = NotificationTemplate::query()
        ->where('event', NotificationEvent::AidAwaitingApproval->value)
        ->pluck('channel')
        ->map(fn (MessageChannel $channel): string => $channel->value)
        ->sort()
        ->values()
        ->all();

    expect($channels)->toBe(['email', 'whatsapp']);
});

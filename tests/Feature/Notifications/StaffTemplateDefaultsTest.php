<?php

use App\Enums\MessageChannel;
use App\Enums\NotificationEvent;
use App\Livewire\Settings\Notifications\Manage;
use App\Models\NotificationTemplate;
use Database\Seeders\NotificationTemplateSeeder;
use Livewire\Livewire;

/**
 * The staff (awaiting-approval) email + WhatsApp templates ship with real,
 * well-written default Arabic bodies, and the settings editor falls back to
 * those shipped defaults whenever a template row is empty — so the admin
 * never sees a blank editor on a fresh install.
 */
it('seeds a non-empty default body for the staff email and whatsapp templates', function () {
    (new NotificationTemplateSeeder)->run();

    $event = NotificationEvent::AidAwaitingApproval->value;

    foreach ([MessageChannel::Email->value, MessageChannel::WhatsApp->value] as $channel) {
        $template = NotificationTemplate::query()
            ->where('event', $event)
            ->where('channel', $channel)
            ->first();

        expect($template)->not->toBeNull();
        expect(trim($template->body))->not->toBe('');
    }
});

it('exposes the shipped default staff email body with all placeholders present', function () {
    $default = NotificationTemplateSeeder::defaultBody(
        NotificationEvent::AidAwaitingApproval->value,
        MessageChannel::Email->value,
    );

    expect(trim($default))->not->toBe('');

    foreach (['{reference}', '{beneficiary}', '{program}', '{stage}', '{link}'] as $placeholder) {
        expect($default)->toContain($placeholder);
    }
});

it('renders the seeded staff email default once its placeholders are substituted', function () {
    $default = NotificationTemplateSeeder::defaultBody(
        NotificationEvent::AidAwaitingApproval->value,
        MessageChannel::Email->value,
    );

    $rendered = strtr($default, [
        '{reference}' => 'AID-2026-014',
        '{beneficiary}' => 'محمد الدوخي',
        '{program}' => 'سلة غذائية',
        '{stage}' => 'اعتماد المدير',
        '{link}' => 'https://example.test/approvals/inbox',
    ]);

    // Every placeholder was substituted (no stray "{...}" tokens remain).
    expect($rendered)->not->toContain('{')
        ->and($rendered)->toContain('AID-2026-014')
        ->and($rendered)->toContain('محمد الدوخي')
        ->and($rendered)->toContain('https://example.test/approvals/inbox');
});

it('falls back to the seeded default in the editor when a template row is empty', function () {
    (new NotificationTemplateSeeder)->run();
    asAdmin();

    // Simulate a stale row that was created before default copy existed.
    NotificationTemplate::query()
        ->where('event', NotificationEvent::AidAwaitingApproval->value)
        ->where('channel', MessageChannel::Email->value)
        ->update(['body' => '']);

    $default = NotificationTemplateSeeder::defaultBody(
        NotificationEvent::AidAwaitingApproval->value,
        MessageChannel::Email->value,
    );

    $field = 'templates.'.NotificationEvent::AidAwaitingApproval->value.'.'.MessageChannel::Email->value.'.body';

    Livewire::test(Manage::class)
        ->assertSet($field, $default);
});

<?php

use App\Livewire\Settings\Notifications\Manage;
use App\Mail\OutboundMessage;
use App\Models\Setting;
use App\Services\Mail\ApplyMailSettings;
use App\Support\Settings;
use Database\Seeders\NotificationTemplateSeeder;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

it('saves SMTP settings, encrypts the password, and never echoes it back to the browser', function () {
    (new NotificationTemplateSeeder)->run();
    asAdmin();

    $rawPassword = 'super-secret-smtp-pass-8080';

    $component = Livewire::test(Manage::class)
        ->set('mailHost', 'smtp.example.com')
        ->set('mailPort', '587')
        ->set('mailEncryption', 'tls')
        ->set('mailUsername', 'mailer@example.com')
        ->set('mailPasswordInput', $rawPassword)
        ->set('mailFromAddress', 'no-reply@almoosacharity.org')
        ->set('mailFromName', 'جمعية الموسى الخيرية')
        ->call('save')
        ->assertHasNoErrors();

    // Never present anywhere in the rendered response.
    $component->assertDontSee($rawPassword);

    // The secret input is cleared server-side after saving.
    expect($component->get('mailPasswordInput'))->toBe('');

    $settings = app(Settings::class);
    expect($settings->get('mail_host'))->toBe('smtp.example.com');
    expect($settings->get('mail_port'))->toBe('587');
    expect($settings->get('mail_encryption'))->toBe('tls');
    expect($settings->get('mail_username'))->toBe('mailer@example.com');
    expect($settings->get('mail_from_address'))->toBe('no-reply@almoosacharity.org');
    expect($settings->get('mail_from_name'))->toBe('جمعية الموسى الخيرية');

    // Password only ever persisted as ciphertext, decryptable via getSecret.
    $stored = Setting::query()->where('key', 'mail_password')->value('value');
    expect($stored)->not->toBeNull()->and($stored)->not->toContain($rawPassword);
    expect($settings->getSecret('mail_password'))->toBe($rawPassword);

    // A fresh mount masks the stored password rather than exposing it.
    Livewire::test(Manage::class)
        ->assertDontSee($rawPassword)
        ->assertSee('•••• 8080');
});

it('leaves a saved mail password untouched when the field is left empty on save', function () {
    (new NotificationTemplateSeeder)->run();
    asAdmin();

    app(Settings::class)->setSecret('mail_password', 'keep-this-pass');

    Livewire::test(Manage::class)
        ->set('mailHost', 'smtp.example.com')
        ->set('mailPasswordInput', '')
        ->call('save')
        ->assertHasNoErrors();

    expect(app(Settings::class)->getSecret('mail_password'))->toBe('keep-this-pass');
});

it('clears the stored mail password via clearMailPassword', function () {
    (new NotificationTemplateSeeder)->run();
    asAdmin();

    app(Settings::class)->setSecret('mail_password', 'to-be-cleared');

    Livewire::test(Manage::class)->call('clearMailPassword');

    expect(app(Settings::class)->getSecret('mail_password'))->toBeNull();
});

it('validates the SMTP fields on save', function () {
    (new NotificationTemplateSeeder)->run();
    asAdmin();

    Livewire::test(Manage::class)
        ->set('mailPort', '99999')          // out of range
        ->set('mailEncryption', 'bogus')     // not in tls|ssl|none
        ->set('mailFromAddress', 'not-an-email')
        ->call('save')
        ->assertHasErrors(['mailPort', 'mailEncryption', 'mailFromAddress']);
});

it('applies the saved SMTP settings over the .env mailer config at send time', function () {
    $settings = app(Settings::class);
    $settings->set('mail_host', 'smtp.saved.example');
    $settings->set('mail_port', '465');
    $settings->set('mail_encryption', 'ssl');
    $settings->set('mail_username', 'saved-user@example.com');
    $settings->setSecret('mail_password', 'saved-pass');
    $settings->set('mail_from_address', 'from@almoosacharity.org');
    $settings->set('mail_from_name', 'Almoosa');

    // Baseline: config still holds the .env defaults, not our saved values.
    expect(config('mail.mailers.smtp.host'))->not->toBe('smtp.saved.example');

    app(ApplyMailSettings::class)->apply();

    expect(config('mail.default'))->toBe('smtp');
    expect(config('mail.mailers.smtp.host'))->toBe('smtp.saved.example');
    expect(config('mail.mailers.smtp.port'))->toBe(465);
    expect(config('mail.mailers.smtp.username'))->toBe('saved-user@example.com');
    expect(config('mail.mailers.smtp.password'))->toBe('saved-pass');
    expect(config('mail.mailers.smtp.scheme'))->toBe('smtps');
    expect(config('mail.from.address'))->toBe('from@almoosacharity.org');
    expect(config('mail.from.name'))->toBe('Almoosa');
});

it('leaves the .env mailer config untouched when no SMTP host is saved', function () {
    $original = config('mail.mailers.smtp.host');

    app(ApplyMailSettings::class)->apply();

    expect(config('mail.mailers.smtp.host'))->toBe($original);
});

it('sends a test email and reports success', function () {
    (new NotificationTemplateSeeder)->run();
    asAdmin();

    Mail::fake();

    $settings = app(Settings::class);
    $settings->set('mail_host', 'smtp.example.com');
    $settings->setSecret('mail_password', 'a-pass');

    $component = Livewire::test(Manage::class)
        ->set('testEmailAddress', 'inbox@example.org')
        ->call('sendTestEmail')
        ->assertHasNoErrors();

    expect($component->get('mailTestResult'))->toMatchArray(['success' => true]);

    Mail::assertSent(OutboundMessage::class, fn (OutboundMessage $mail): bool => $mail->hasTo('inbox@example.org'));
});

it('requires a valid recipient address for the test email', function () {
    (new NotificationTemplateSeeder)->run();
    asAdmin();

    Mail::fake();

    Livewire::test(Manage::class)
        ->set('testEmailAddress', 'not-an-email')
        ->call('sendTestEmail')
        ->assertHasErrors(['testEmailAddress']);

    Mail::assertNothingSent();
});

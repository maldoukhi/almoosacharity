<?php

use App\Actions\Messaging\SendBroadcast;
use App\Enums\MessageChannel;
use App\Livewire\Messaging\Broadcast;
use App\Mail\OutboundMessage;
use App\Models\MessageLog;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

/**
 * Feature 12 (email side): the group broadcast can send email to a list of
 * addresses, attaching the optional file to each message. SMS/WhatsApp are
 * unaffected (covered by the existing broadcast suites).
 */
beforeEach(function () {
    Storage::fake('local');
    Mail::fake();
});

it('sends an email broadcast to the manual addresses with the attachment', function () {
    $actor = asManager();

    $path = UploadedFile::fake()->create('flyer.pdf', 50, 'application/pdf')
        ->store('broadcast-attachments', 'local');

    app(SendBroadcast::class)->handle(
        beneficiaryIds: [],
        channel: 'email',
        body: 'مرحبًا {name}، هذه رسالة تجريبية.',
        templateName: null,
        actor: $actor,
        manualNumbers: ['first@example.org', 'second@example.org'],
        attachment: ['disk' => 'local', 'path' => $path, 'type' => 'document'],
    );

    Mail::assertSent(OutboundMessage::class, 2);
    Mail::assertSent(OutboundMessage::class, fn (OutboundMessage $mail): bool => $mail->hasTo('first@example.org')
        && $mail->attachment !== null
        && $mail->attachment['disk'] === 'local'
        && $mail->attachment['path'] === $path);

    expect(MessageLog::query()->where('channel', MessageChannel::Email)->count())->toBe(2);
    expect(MessageLog::query()->where('channel', MessageChannel::Email)->pluck('recipient')->all())
        ->toContain('first@example.org', 'second@example.org');
});

it('does not target beneficiaries on the email channel (no email on file)', function () {
    $actor = asManager();

    app(SendBroadcast::class)->handle(
        beneficiaryIds: [999], // even if it existed, beneficiaries carry no email
        channel: 'email',
        body: 'مرحبًا {name}',
        templateName: null,
        actor: $actor,
        manualNumbers: ['only@example.org'],
    );

    Mail::assertSent(OutboundMessage::class, 1);
    Mail::assertSent(OutboundMessage::class, fn (OutboundMessage $mail): bool => $mail->hasTo('only@example.org'));
});

it('offers email as a selectable channel and validates addresses on the broadcast screen', function () {
    asManager();

    $component = Livewire::test(Broadcast::class)
        ->set('channel', MessageChannel::Email->value)
        ->set('manualNumbers', "valid@example.org\nnot-an-email");

    expect($component->get('manualNumbersValid'))->toHaveCount(1)
        ->and($component->get('manualNumbersInvalid'))->toHaveCount(1)
        ->and($component->get('eligibleCount'))->toBe(1);
});

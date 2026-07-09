<?php

use App\Actions\Beneficiaries\CreateBeneficiary;
use App\Actions\Messaging\SendBroadcast;
use App\Livewire\Messaging\Broadcast;
use App\Models\MessageLog;
use App\Services\Messaging\Drivers\FakeSmsGateway;
use App\Services\Messaging\Drivers\FakeWhatsAppGateway;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

/**
 * See {@see createBeneficiaryWithMobile()} in SendBroadcastTest.php: mobile
 * is NOT NULL, so this creates a beneficiary with a real number to exercise
 * the WhatsApp/SMS attachment paths end-to-end (queue is sync in tests, so
 * the per-recipient send jobs run through the fake gateways immediately).
 */
function attachmentBeneficiary(string $mobile)
{
    $actor = asDataEntry();

    return app(CreateBeneficiary::class)->handle(
        beneficiaryAttributes([
            'national_id' => (string) random_int(3000000000, 3999999999),
            'mobile' => $mobile,
            'first_name' => 'ريم',
            'second_name' => '',
            'third_name' => '',
            'last_name' => 'الحربي',
        ]),
        [],
        $actor,
    );
}

beforeEach(function () {
    Storage::fake('local');
    FakeWhatsAppGateway::reset();
    FakeSmsGateway::reset();
});

it('delivers an attached file on a whatsapp broadcast as a media message carrying the media url and caption', function () {
    $beneficiary = attachmentBeneficiary('0511111111');
    $actor = asManager();

    $path = UploadedFile::fake()->create('report.pdf', 100, 'application/pdf')
        ->store('broadcast-attachments', 'local');

    app(SendBroadcast::class)->handle(
        beneficiaryIds: [$beneficiary->id],
        channel: 'whatsapp',
        body: 'مرحبًا {name}',
        templateName: null,
        actor: $actor,
        manualNumbers: [],
        attachment: ['disk' => 'local', 'path' => $path, 'type' => 'document'],
    );

    expect(FakeWhatsAppGateway::$sent)->toHaveCount(1)
        ->and(FakeWhatsAppGateway::$sent[0]['type'])->toBe('media')
        ->and(FakeWhatsAppGateway::$sent[0]['media_type'])->toBe('document')
        ->and(FakeWhatsAppGateway::$sent[0]['media_url'])->not->toBeEmpty()
        ->and(FakeWhatsAppGateway::$sent[0]['caption'])->toBe("مرحبًا {$beneficiary->full_name}");

    // The audit trail still records the send, with the caption as its body.
    $log = MessageLog::query()->latest('id')->first();
    expect($log->body)->toBe("مرحبًا {$beneficiary->full_name}");
});

it('falls back to a plain text whatsapp send when no attachment is present', function () {
    $beneficiary = attachmentBeneficiary('0511111111');
    $actor = asManager();

    app(SendBroadcast::class)->handle(
        beneficiaryIds: [$beneficiary->id],
        channel: 'whatsapp',
        body: 'مرحبًا {name}',
        templateName: null,
        actor: $actor,
    );

    expect(FakeWhatsAppGateway::$sent)->toHaveCount(1)
        ->and(FakeWhatsAppGateway::$sent[0]['type'])->toBe('text');
});

it('ignores an attachment on an sms broadcast and sends text only', function () {
    $beneficiary = attachmentBeneficiary('0511111111');
    $actor = asManager();

    $path = UploadedFile::fake()->create('report.pdf', 100, 'application/pdf')
        ->store('broadcast-attachments', 'local');

    app(SendBroadcast::class)->handle(
        beneficiaryIds: [$beneficiary->id],
        channel: 'sms',
        body: 'مرحبًا {name}',
        templateName: null,
        actor: $actor,
        manualNumbers: [],
        attachment: ['disk' => 'local', 'path' => $path, 'type' => 'document'],
    );

    // The SMS still goes out, but nothing touched the WhatsApp/media path.
    expect(FakeSmsGateway::$sent)->toHaveCount(1)
        ->and(FakeWhatsAppGateway::$sent)->toBeEmpty();
});

it('stores the uploaded file and sends it as whatsapp media through the livewire screen', function () {
    $beneficiary = attachmentBeneficiary('0511111111');
    asManager();

    Livewire::withQueryParams(['ids' => (string) $beneficiary->id])
        ->test(Broadcast::class)
        ->set('channel', 'whatsapp')
        ->set('body', 'مرحبًا {name}')
        ->set('attachment', UploadedFile::fake()->image('flyer.png'))
        ->call('send')
        ->assertHasNoErrors();

    expect(FakeWhatsAppGateway::$sent)->toHaveCount(1)
        ->and(FakeWhatsAppGateway::$sent[0]['type'])->toBe('media')
        ->and(FakeWhatsAppGateway::$sent[0]['media_type'])->toBe('image');
});

it('drops the pending attachment when the channel is switched to sms', function () {
    $beneficiary = attachmentBeneficiary('0511111111');
    asManager();

    Livewire::withQueryParams(['ids' => (string) $beneficiary->id])
        ->test(Broadcast::class)
        ->set('channel', 'whatsapp')
        ->set('attachment', UploadedFile::fake()->create('report.pdf', 100, 'application/pdf'))
        ->assertSet('attachment', fn ($value) => $value !== null)
        ->set('channel', 'sms')
        ->assertSet('attachment', null);
});

it('rejects an attachment whose type is not pdf/jpg/png', function () {
    $beneficiary = attachmentBeneficiary('0511111111');
    asManager();

    Livewire::withQueryParams(['ids' => (string) $beneficiary->id])
        ->test(Broadcast::class)
        ->set('channel', 'whatsapp')
        ->set('attachment', UploadedFile::fake()->create('malware.exe', 10, 'application/octet-stream'))
        ->assertHasErrors('attachment');
});

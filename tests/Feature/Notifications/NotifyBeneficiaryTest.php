<?php

use App\Enums\AidProgramType;
use App\Enums\AidType;
use App\Enums\MessageChannel;
use App\Enums\NotificationEvent;
use App\Models\AidProgram;
use App\Models\Beneficiary;
use App\Models\MessageLog;
use App\Models\NotificationTemplate;
use App\Services\Notifications\NotifyBeneficiary;
use App\Support\Settings;
use Database\Factories\AidFactory;

function cashAidForBeneficiary(array $beneficiaryOverrides = [])
{
    seedAidCatalog();
    $program = AidProgram::query()->where('type', AidProgramType::Cash)->firstOrFail();
    $beneficiary = Beneficiary::factory()->create($beneficiaryOverrides);

    return AidFactory::new()->approved()->create([
        'beneficiary_id' => $beneficiary->id,
        'aid_program_id' => $program->id,
        'type' => AidType::Cash,
        'amount' => 1500,
    ]);
}

it('creates a message_log with variables substituted when the sms channel and template are active', function () {
    asDataEntry();

    app(Settings::class)->set('sms_enabled', '1');
    app(Settings::class)->set('whatsapp_enabled', '0');

    NotificationTemplate::query()->create([
        'event' => NotificationEvent::AidApproved,
        'channel' => MessageChannel::Sms,
        'body' => 'مرحبا {name}، تمت الموافقة على مبلغ {amount} ريال ضمن برنامج {program}.',
        'is_active' => true,
    ]);

    $aid = cashAidForBeneficiary(['mobile' => '0512345678', 'first_name' => 'سارة']);

    app(NotifyBeneficiary::class)->send($aid, NotificationEvent::AidApproved);

    $log = MessageLog::query()->where('recipient', '0512345678')->latest('id')->first();

    expect($log)->not->toBeNull();
    expect($log->channel)->toBe(MessageChannel::Sms);
    expect($log->body)->toContain('1,500.00');
    expect($log->body)->toContain($aid->fresh()->program->name);
    expect($log->body)->not->toContain('{name}');
    expect($log->body)->not->toContain('{amount}');
});

it('sends nothing when the sms channel is disabled in settings, even with an active template', function () {
    asDataEntry();

    app(Settings::class)->set('sms_enabled', '0');
    app(Settings::class)->set('whatsapp_enabled', '0');

    NotificationTemplate::query()->create([
        'event' => NotificationEvent::AidApproved,
        'channel' => MessageChannel::Sms,
        'body' => 'مرحبا {name}',
        'is_active' => true,
    ]);

    $aid = cashAidForBeneficiary(['mobile' => '0555555555']);

    app(NotifyBeneficiary::class)->send($aid, NotificationEvent::AidApproved);

    expect(MessageLog::query()->where('recipient', '0555555555')->exists())->toBeFalse();
});

it('does not crash and sends nothing for a beneficiary with no mobile number on file', function () {
    asDataEntry();

    app(Settings::class)->set('sms_enabled', '1');

    NotificationTemplate::query()->create([
        'event' => NotificationEvent::AidApproved,
        'channel' => MessageChannel::Sms,
        'body' => 'مرحبا {name}',
        'is_active' => true,
    ]);

    // The beneficiaries.mobile column is NOT NULL; an empty string is the
    // realistic "no mobile on file" shape and is still `blank()`.
    $aid = cashAidForBeneficiary(['mobile' => '']);

    app(NotifyBeneficiary::class)->send($aid, NotificationEvent::AidApproved);

    expect(MessageLog::query()->count())->toBe(0);
});

<?php

use App\Actions\Disbursements\RecordDelivery;
use App\Enums\AidProgramType;
use App\Enums\AidStatus;
use App\Enums\AidType;
use App\Enums\DisbursementMethod;
use App\Enums\DisbursementStatus;
use App\Enums\MessageChannel;
use App\Enums\NotificationEvent;
use App\Models\AidProgram;
use App\Models\Beneficiary;
use App\Models\Disbursement;
use App\Models\MessageLog;
use App\Models\NotificationTemplate;
use App\Services\Notifications\NotifyBeneficiary;
use App\Support\Settings;
use Database\Factories\AidFactory;
use Database\Seeders\NotificationTemplateSeeder;

/**
 * Feature 5: the {short_name} placeholder (beneficiary first + last name)
 * must be substituted anywhere {name} is available.
 */
it('substitutes the {short_name} placeholder with the beneficiary first and last name', function () {
    asDataEntry();

    app(Settings::class)->set('sms_enabled', '1');
    app(Settings::class)->set('whatsapp_enabled', '0');

    NotificationTemplate::query()->create([
        'event' => NotificationEvent::AidApproved,
        'channel' => MessageChannel::Sms,
        'body' => 'أهلًا {short_name}، بخصوص إعانتك.',
        'is_active' => true,
    ]);

    seedAidCatalog();
    $program = AidProgram::query()->where('type', AidProgramType::Cash)->firstOrFail();
    $beneficiary = Beneficiary::factory()->create([
        'mobile' => '0512345678',
        'first_name' => 'محمد',
        'second_name' => 'عبدالله',
        'third_name' => 'سعد',
        'last_name' => 'الأحمد',
    ]);

    $aid = AidFactory::new()->approved()->create([
        'beneficiary_id' => $beneficiary->id,
        'aid_program_id' => $program->id,
        'type' => AidType::Cash,
        'amount' => 1000,
    ]);

    app(NotifyBeneficiary::class)->send($aid, NotificationEvent::AidApproved);

    $log = MessageLog::query()->where('recipient', '0512345678')->latest('id')->first();

    expect($log)->not->toBeNull();
    // Short name is first + last only, skipping the middle parts.
    expect($log->body)->toContain('أهلًا محمد الأحمد،');
    expect($log->body)->not->toContain('{short_name}');
    expect($log->body)->not->toContain('عبدالله');
});

/**
 * Feature 4: helper to run RecordDelivery for a fresh cash aid.
 */
function deliverCashAid(): Beneficiary
{
    $actor = asDataEntry();

    seedAidCatalog();
    $program = AidProgram::query()->where('type', AidProgramType::Cash)->firstOrFail();
    $beneficiary = Beneficiary::factory()->create(['mobile' => '0501112222']);

    $aid = AidFactory::new()->approved()->create([
        'beneficiary_id' => $beneficiary->id,
        'aid_program_id' => $program->id,
        'type' => AidType::Cash,
        'amount' => 800,
        'status' => AidStatus::InDisbursement,
    ]);

    $disbursement = Disbursement::factory()->create([
        'aid_id' => $aid->id,
        'method' => DisbursementMethod::OfficePickup,
        'status' => DisbursementStatus::Pending,
    ]);

    app(RecordDelivery::class)->handle($disbursement, $actor, ['receipt_number' => 'RCPT-9']);

    return $beneficiary;
}

it('sends two separate messages on delivery when combined mode is off (default)', function () {
    (new NotificationTemplateSeeder)->run();

    app(Settings::class)->set('combined_delivery_message', '0');

    deliverCashAid();

    $logs = MessageLog::query()->where('recipient', '0501112222')->get();

    // One "aid delivered" notice + one confirmation link message.
    expect($logs)->toHaveCount(2);
    expect($logs->contains(fn (MessageLog $log): bool => str_contains($log->body, 'http')))->toBeTrue();
    expect($logs->contains(fn (MessageLog $log): bool => str_contains($log->body, 'تم تسليم') && ! str_contains($log->body, 'http')))->toBeTrue();
});

it('sends a single combined message on delivery when combined mode is on', function () {
    (new NotificationTemplateSeeder)->run();

    app(Settings::class)->set('combined_delivery_message', '1');

    deliverCashAid();

    $logs = MessageLog::query()->where('recipient', '0501112222')->get();

    // Exactly one message: the standalone delivery notice is suppressed and
    // its text folded into the confirmation-link message.
    expect($logs)->toHaveCount(1);

    $body = $logs->first()->body;

    // The single message carries both the delivery notice and the link.
    expect($body)->toContain('تم تسليم');
    expect($body)->toContain('http');
});

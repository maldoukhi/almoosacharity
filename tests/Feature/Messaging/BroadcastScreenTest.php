<?php

use App\Actions\Beneficiaries\CreateBeneficiary;
use App\Jobs\Messaging\SendSmsMessage;
use App\Livewire\Messaging\Broadcast;
use App\Models\MessageLog;
use App\Models\MessageTemplate;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

/**
 * See {@see createBeneficiaryWithMobile()} in SendBroadcastTest.php: mobile
 * is NOT NULL, so a blank string stands in for "no mobile on file" here.
 */
function broadcastBeneficiary(?string $mobile, array $overrides = [])
{
    $actor = asDataEntry();

    return app(CreateBeneficiary::class)->handle(
        beneficiaryAttributes(array_merge([
            'national_id' => (string) random_int(2000000000, 2999999999),
            'mobile' => $mobile ?? '',
        ], $overrides)),
        [],
        $actor,
    );
}

it('forbids a user without messages.broadcast from mounting the screen', function () {
    asResearcher();

    Livewire::test(Broadcast::class)->assertForbidden();
});

it('throttles a manager after 5 broadcasts in a minute', function () {
    Queue::fake();
    asManager();

    $component = Livewire::test(Broadcast::class);

    // send() clears the form on success, so re-populate a valid recipient
    // + body before each attempt; the 6th must be blocked by the throttle.
    $attempt = function () use ($component): void {
        $component->set('manualNumbers', '0511111111')->set('body', 'مرحبا')->call('send');
    };

    for ($i = 0; $i < 5; $i++) {
        $attempt();
        $component->assertHasNoErrors();
    }

    $attempt();
    $component->assertHasErrors('body');
});

it('rejects a send that exceeds the recipient cap without dispatching anything', function () {
    Queue::fake();
    broadcastBeneficiary('0511111111');
    asManager();

    // 2001 manual numbers > MAX_RECIPIENTS (2000)
    $numbers = collect(range(0, 2000))
        ->map(fn (int $i): string => '05'.str_pad((string) $i, 8, '0', STR_PAD_LEFT))
        ->implode("\n");

    Livewire::test(Broadcast::class)
        ->set('body', 'مرحبا')
        ->set('selectedIds', [])
        ->set('manualNumbers', $numbers)
        ->call('send')
        ->assertHasErrors('body');

    Queue::assertNotPushed(SendSmsMessage::class);
});

it('lets a manager mount the screen and defaults to all filtered beneficiaries with a mobile selected', function () {
    $one = broadcastBeneficiary('0511111111');
    $two = broadcastBeneficiary('0522222222');
    broadcastBeneficiary(null); // no mobile: never selected by default

    asManager();

    Livewire::test(Broadcast::class)
        ->assertSet('selectAllFiltered', true)
        ->assertOk()
        ->tap(function ($component) use ($one, $two): void {
            expect($component->get('selectedIds'))->toEqualCanonicalizing([$one->id, $two->id]);
        });
});

it('reads a fixed ids list from the query string and disables select-all-filtered', function () {
    $one = broadcastBeneficiary('0511111111');
    $two = broadcastBeneficiary('0522222222');
    $three = broadcastBeneficiary('0533333333');

    asManager();

    Livewire::withQueryParams(['ids' => "{$one->id},{$three->id}"])
        ->test(Broadcast::class)
        ->assertSet('selectAllFiltered', false)
        ->tap(function ($component) use ($one, $three, $two): void {
            $ids = $component->get('selectedIds');
            expect($ids)->toContain($one->id)
                ->and($ids)->toContain($three->id)
                ->and($ids)->not->toContain($two->id);
        });
});

it('replaces {name} in the live preview with the first selected beneficiary name', function () {
    $beneficiary = broadcastBeneficiary('0511111111', ['first_name' => 'ريم', 'last_name' => 'الحربي']);

    asManager();

    Livewire::withQueryParams(['ids' => (string) $beneficiary->id])
        ->test(Broadcast::class)
        ->set('body', 'مرحبًا {name}، كيف حالك؟')
        ->assertSee("مرحبًا {$beneficiary->full_name}، كيف حالك؟");
});

it('sends a broadcast, saves a template when requested, and reports the recipient count', function () {
    Queue::fake();

    $one = broadcastBeneficiary('0511111111');
    $two = broadcastBeneficiary('0522222222');

    asManager();

    Livewire::withQueryParams(['ids' => "{$one->id},{$two->id}"])
        ->test(Broadcast::class)
        ->set('channel', 'sms')
        ->set('body', 'مرحبًا {name}، تذكير من الجمعية.')
        ->set('saveAsTemplate', true)
        ->set('newTemplateName', 'تذكير عام')
        ->call('send')
        ->assertHasNoErrors()
        ->assertDispatched('toast', type: 'success', message: __('messaging.broadcast.sent', ['count' => 2]));

    Queue::assertPushed(SendSmsMessage::class, 2);

    expect(MessageTemplate::query()->where('name', 'تذكير عام')->exists())->toBeTrue();
});

it('rejects an empty body on send', function () {
    $beneficiary = broadcastBeneficiary('0511111111');

    asManager();

    Livewire::withQueryParams(['ids' => (string) $beneficiary->id])
        ->test(Broadcast::class)
        ->set('body', '')
        ->call('send')
        ->assertHasErrors(['body' => 'required']);
});

it('counts a manual number entered per line as valid and includes it in the eligible total', function () {
    $beneficiary = broadcastBeneficiary('0511111111');

    asManager();

    Livewire::withQueryParams(['ids' => (string) $beneficiary->id])
        ->test(Broadcast::class)
        ->set('manualNumbers', "0522222222\n0533333333")
        ->assertSet('manualNumbersInvalid', [])
        ->tap(function ($component): void {
            expect($component->get('manualNumbersValid'))->toEqualCanonicalizing(['0522222222', '0533333333']);
            expect($component->instance()->eligibleCount)->toBe(3); // 1 beneficiary + 2 manual
        });
});

it('flags an invalid manual number as a specific validation error and blocks sending', function () {
    asManager();

    Livewire::test(Broadcast::class)
        ->set('selectedIds', [])
        ->set('body', 'مرحبًا {name}')
        ->set('manualNumbers', "0522222222\n123") // second one is not a valid Saudi mobile
        ->tap(function ($component): void {
            expect($component->get('manualNumbersInvalid'))->toBe(['123']);
        })
        ->call('send')
        ->assertHasErrors(['manualNumbers']);
});

it('treats a comma-separated duplicate of a selected beneficiary mobile as already covered, not double-counted', function () {
    $beneficiary = broadcastBeneficiary('0511111111');

    asManager();

    Livewire::withQueryParams(['ids' => (string) $beneficiary->id])
        ->test(Broadcast::class)
        ->set('manualNumbers', '0511111111,0522222222')
        ->tap(function ($component): void {
            // 0511111111 is already the selected beneficiary's mobile:
            // the union is 2, not 3.
            expect($component->instance()->eligibleCount)->toBe(2);
        });
});

it('sends a broadcast using only manual numbers, with no beneficiaries selected', function () {
    Queue::fake();

    asManager();

    Livewire::test(Broadcast::class)
        ->set('selectedIds', [])
        ->set('selectAllFiltered', false)
        ->set('channel', 'sms')
        ->set('body', 'مرحبًا {name}، تذكير من الجمعية.')
        ->set('manualNumbers', "0511111111\n0522222222")
        ->call('send')
        ->assertHasNoErrors()
        ->assertDispatched('toast', type: 'success', message: __('messaging.broadcast.sent', ['count' => 2]));

    Queue::assertPushed(SendSmsMessage::class, 2);

    $logs = MessageLog::query()->where('body', 'like', '%'.__('messaging.default_recipient_name').'%')->get();
    expect($logs)->toHaveCount(2);
});

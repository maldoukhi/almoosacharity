<?php

use App\Enums\MessageChannel;
use App\Enums\NotificationEvent;
use App\Livewire\Settings\Notifications\Manage;
use App\Models\NotificationTemplate;
use App\Models\Setting;
use App\Services\Messaging\Drivers\OktaWhatsAppGateway;
use App\Support\Settings;
use Database\Seeders\NotificationTemplateSeeder;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response as Psr7Response;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Okta\Connect\WhatsApp\Config as SdkConfig;
use Okta\Connect\WhatsApp\Http\HttpClient as SdkHttpClient;

/**
 * Binds OktaWhatsAppGateway::class to a factory that injects a mocked SDK
 * HTTP transport (a queue of canned Guzzle responses), so Livewire-level
 * verify()/QR pairing actions never touch the network — the vendor SDK
 * talks to Guzzle directly and isn't reachable through Http::fake().
 */
function bindFakeOktaGateway(array $responseQueue): void
{
    $guzzle = new GuzzleClient(['handler' => HandlerStack::create(new MockHandler($responseQueue)), 'http_errors' => false]);

    $sdkHttpClient = new SdkHttpClient(new SdkConfig(
        baseUrl: 'https://connect.example.com',
        token: 'test-token',
        retries: 0,
        httpClient: $guzzle,
    ));

    app()->bind(OktaWhatsAppGateway::class, fn ($app, $params) => new OktaWhatsAppGateway(
        baseUrl: $params['baseUrl'] ?? 'https://connect.example.com',
        token: $params['token'] ?? 'test-token',
        channelId: $params['channelId'] ?? 'ch_1',
        httpClient: $sdkHttpClient,
    ));
}

it('forbids a user without notifications.settings.manage from opening the settings screen', function () {
    asManager();

    Livewire::test(Manage::class)->assertForbidden();
});

it('forbids a user without notifications.settings.manage from calling any provider action', function () {
    asManager();

    Livewire::test(Manage::class)->assertForbidden();

    // mount() itself already throws for an unauthorized user (asserted
    // above), so directly instantiating the underlying class is the only
    // way to reach each individual action and confirm it re-checks the
    // gate rather than relying solely on mount().
    $component = new Manage;

    foreach ([
        'verifyTaqnyat', 'verifyOkta', 'clearTaqnyatApiKey', 'clearOktaToken', 'clearOktaChannel',
        'startWhatsappQrPairing', 'pollWhatsappQrStatus', 'closeWhatsappQrPairing', 'fetchOktaChannels', 'fetchSenders',
    ] as $action) {
        expect(fn () => $component->{$action}())
            ->toThrow(AuthorizationException::class);
    }
});

it('lets an authorized admin update a template body and the channel toggles', function () {
    (new NotificationTemplateSeeder)->run();
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

it('rejects a confirmation body that is missing the {link} placeholder, and saves a valid one', function () {
    (new NotificationTemplateSeeder)->run();
    asAdmin();

    // Missing {link} — must fail validation and persist nothing.
    Livewire::test(Manage::class)
        ->set('confirmationBody', 'مرحبًا {name}، تم تسليم إعانتك.')
        ->call('save')
        ->assertHasErrors(['confirmationBody']);

    expect(app(Settings::class)->get('confirmation_body'))->toBeNull();

    // With {link} present — saves successfully.
    $valid = 'مرحبًا {name}، أكّد استلامك من هنا: {link}';

    Livewire::test(Manage::class)
        ->set('confirmationBody', $valid)
        ->call('save')
        ->assertHasNoErrors();

    expect(app(Settings::class)->get('confirmation_body'))->toBe($valid);
});

it('saves the Taqnyat API key encrypted, resets the input, and never leaks it back to the browser', function () {
    (new NotificationTemplateSeeder)->run();
    asAdmin();

    $rawKey = 'super-secret-taqnyat-key-9999';

    $component = Livewire::test(Manage::class)
        ->set('taqnyatApiKeyInput', $rawKey)
        ->call('save')
        ->assertHasNoErrors();

    // Never present in the response HTML (attribute values included).
    $component->assertDontSee($rawKey);

    // The input is cleared server-side immediately after saving.
    expect($component->get('taqnyatApiKeyInput'))->toBe('');

    // Persisted, but only as ciphertext.
    $stored = Setting::query()->where('key', 'taqnyat_api_key')->value('value');
    expect($stored)->not->toBeNull()->and($stored)->not->toContain($rawKey);
    expect(app(Settings::class)->getSecret('taqnyat_api_key'))->toBe($rawKey);

    // A brand new mount of the screen still never exposes the raw key,
    // only the masked last-4 placeholder.
    Livewire::test(Manage::class)
        ->assertDontSee($rawKey)
        ->assertSee('•••• 9999');
});

it('saves the Okta token encrypted alongside the plain base_url/channel_id fields', function () {
    (new NotificationTemplateSeeder)->run();
    asAdmin();

    Livewire::test(Manage::class)
        ->set('oktaBaseUrl', 'https://connect.example.com')
        ->set('oktaChannelId', 'ch_saved')
        ->set('oktaTokenInput', 'super-secret-okta-token')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDontSee('super-secret-okta-token');

    $settings = app(Settings::class);
    expect($settings->get('okta_base_url'))->toBe('https://connect.example.com');
    expect($settings->get('okta_channel_id'))->toBe('ch_saved');
    expect($settings->getSecret('okta_token'))->toBe('super-secret-okta-token');

    $stored = Setting::query()->where('key', 'okta_token')->value('value');
    expect($stored)->not->toContain('super-secret-okta-token');
});

it('leaves a saved secret untouched when the field is left empty on save', function () {
    (new NotificationTemplateSeeder)->run();
    asAdmin();

    app(Settings::class)->setSecret('taqnyat_api_key', 'keep-me');

    Livewire::test(Manage::class)
        ->set('taqnyatApiKeyInput', '')
        ->call('save')
        ->assertHasNoErrors();

    expect(app(Settings::class)->getSecret('taqnyat_api_key'))->toBe('keep-me');
});

it('clears a stored secret via the dedicated clear actions', function () {
    (new NotificationTemplateSeeder)->run();
    asAdmin();

    $settings = app(Settings::class);
    $settings->setSecret('taqnyat_api_key', 'to-be-cleared');
    $settings->setSecret('okta_token', 'also-cleared');

    Livewire::test(Manage::class)
        ->call('clearTaqnyatApiKey')
        ->call('clearOktaToken');

    expect(app(Settings::class)->getSecret('taqnyat_api_key'))->toBeNull();
    expect(app(Settings::class)->getSecret('okta_token'))->toBeNull();
});

it('clears a stale channel id via clearOktaChannel without touching other Okta settings', function () {
    (new NotificationTemplateSeeder)->run();
    asAdmin();

    $settings = app(Settings::class);
    $settings->set('okta_base_url', 'https://connect.example.com');
    $settings->set('okta_channel_id', 'admin@almoosacharity.org');

    $component = Livewire::test(Manage::class)
        ->call('clearOktaChannel');

    expect(app(Settings::class)->get('okta_channel_id'))->toBeNull();
    expect(app(Settings::class)->get('okta_base_url'))->toBe('https://connect.example.com');
    expect($component->get('oktaChannelId'))->toBe('');
});

it('opens the QR pairing modal as soon as pairing starts, and closes it via closeWhatsappQrPairing', function () {
    (new NotificationTemplateSeeder)->run();
    asAdmin();

    // No base_url/token configured — startWhatsappQrPairing bails out early
    // with a missing-credentials message, but the modal must still open so
    // that message is visible to the operator.
    $component = Livewire::test(Manage::class)
        ->call('startWhatsappQrPairing');

    expect($component->get('whatsappQrModalOpen'))->toBeTrue();

    $component->call('closeWhatsappQrPairing');

    expect($component->get('whatsappQrModalOpen'))->toBeFalse()
        ->and($component->get('whatsappQrChannelId'))->toBeNull()
        ->and($component->get('whatsappQrText'))->toBeNull()
        ->and($component->get('whatsappQrStatus'))->toBeNull()
        ->and($component->get('whatsappQrPolling'))->toBeFalse()
        ->and($component->get('whatsappQrMessage'))->toBeNull();
});

it('verifies the Taqnyat connection and shows the balance on success', function () {
    (new NotificationTemplateSeeder)->run();
    asAdmin();

    Http::fake([
        'api.taqnyat.sa/account/balance' => Http::response(['balance' => '75.00'], 200),
    ]);

    $component = Livewire::test(Manage::class)
        ->set('taqnyatApiKeyInput', 'typed-not-yet-saved-key')
        ->call('verifyTaqnyat');

    expect($component->get('taqnyatVerifyResult'))->toMatchArray([
        'success' => true,
        'balance' => '75.00',
    ]);
});

it('fetches the available Taqnyat sender names and offers them for selection', function () {
    (new NotificationTemplateSeeder)->run();
    asAdmin();

    Http::fake([
        'api.taqnyat.sa/v1/messages/senders' => Http::response([
            'senders' => [
                ['name' => 'ALMOOSA', 'status' => 'accepted'],
                ['name' => 'PENDINGNAME', 'status' => 'pending'],
            ],
        ], 200),
    ]);

    $component = Livewire::test(Manage::class)
        ->set('taqnyatApiKeyInput', 'typed-not-yet-saved-key')
        ->call('fetchSenders');

    expect($component->get('sendersLoaded'))->toBeTrue()
        ->and($component->get('availableSenders'))->toBe([
            ['name' => 'ALMOOSA', 'status' => 'accepted'],
        ]);

    $component
        ->set('senderSelection', 'ALMOOSA')
        ->assertSet('senderName', 'ALMOOSA');
});

it('shows an error toast and leaves the sender list empty when fetchSenders fails', function () {
    (new NotificationTemplateSeeder)->run();
    asAdmin();

    Http::fake([
        'api.taqnyat.sa/v1/messages/senders' => Http::response(['message' => 'Invalid token.'], 401),
    ]);

    $component = Livewire::test(Manage::class)
        ->set('taqnyatApiKeyInput', 'a-key')
        ->call('fetchSenders');

    expect($component->get('sendersLoaded'))->toBeFalse()
        ->and($component->get('availableSenders'))->toBe([]);
});

it('shows a friendly failure badge when Taqnyat verify fails', function () {
    (new NotificationTemplateSeeder)->run();
    asAdmin();

    Http::fake([
        'api.taqnyat.sa/account/balance' => Http::response(['message' => 'Invalid token.'], 401),
    ]);

    $component = Livewire::test(Manage::class)
        ->set('taqnyatApiKeyInput', 'a-key')
        ->call('verifyTaqnyat');

    expect($component->get('taqnyatVerifyResult'))->toMatchArray([
        'success' => false,
        'message' => 'Invalid token.',
    ]);
});

it('shows a missing-configuration result for verifyOkta without making any HTTP call', function () {
    (new NotificationTemplateSeeder)->run();
    asAdmin();

    $component = Livewire::test(Manage::class)->call('verifyOkta');

    expect($component->get('oktaVerifyResult')['success'])->toBeFalse();
});

it('verifies the Okta connection via the configured channel', function () {
    (new NotificationTemplateSeeder)->run();
    asAdmin();

    bindFakeOktaGateway([
        new Psr7Response(200, ['Content-Type' => 'application/json'], json_encode([
            'data' => ['id' => 'ch_1', 'display_name' => 'Main', 'status' => 'connected'],
        ])),
    ]);

    $component = Livewire::test(Manage::class)
        ->set('oktaBaseUrl', 'https://connect.example.com')
        ->set('oktaChannelId', 'ch_1')
        ->set('oktaTokenInput', 'a-token')
        ->call('verifyOkta');

    expect($component->get('oktaVerifyResult'))->toMatchArray([
        'success' => true,
        'status' => 'connected',
    ]);
});

it('fetches existing Okta channels so an operator can link one', function () {
    (new NotificationTemplateSeeder)->run();
    asAdmin();

    bindFakeOktaGateway([
        new Psr7Response(200, ['Content-Type' => 'application/json'], json_encode([
            'data' => [
                ['id' => 'ch_a', 'display_name' => 'Almoosa WhatsApp', 'status' => 'connected'],
                ['id' => 'ch_b', 'display_name' => 'Backup', 'status' => 'pending'],
            ],
        ])),
    ]);

    $component = Livewire::test(Manage::class)
        ->set('oktaBaseUrl', 'https://connect.example.com')
        ->set('oktaTokenInput', 'a-token')
        ->call('fetchOktaChannels');

    expect($component->get('oktaChannelsFetched'))->toBeTrue()
        ->and($component->get('oktaChannels'))->toHaveCount(2)
        ->and($component->get('oktaChannels')[0])->toMatchArray(['id' => 'ch_a', 'name' => 'Almoosa WhatsApp', 'status' => 'connected']);
});

it('links an existing channel by persisting its id as the active channel', function () {
    (new NotificationTemplateSeeder)->run();
    asAdmin();

    Livewire::test(Manage::class)
        ->call('useOktaChannel', 'ch_existing')
        ->assertDispatched('toast');

    expect(app(Settings::class)->get('okta_channel_id'))->toBe('ch_existing');
});

it('runs the full QR pairing flow through to a connected channel, persisting the new channel id', function () {
    (new NotificationTemplateSeeder)->run();
    asAdmin();

    bindFakeOktaGateway([
        new Psr7Response(201, ['Content-Type' => 'application/json'], json_encode([
            'channel' => ['id' => 'ch_new', 'display_name' => 'Almoosa Charity WhatsApp', 'status' => 'pending'],
            'qr' => 'RAW-QR-PAYLOAD',
            'qr_ttl_seconds' => 60,
        ])),
        new Psr7Response(200, ['Content-Type' => 'application/json'], json_encode([
            'channel' => ['id' => 'ch_new', 'display_name' => 'Almoosa Charity WhatsApp', 'status' => 'connected'],
            'qr' => null,
            'qr_ttl_seconds' => null,
        ])),
    ]);

    $component = Livewire::test(Manage::class)
        ->set('oktaBaseUrl', 'https://connect.example.com')
        ->set('oktaTokenInput', 'a-token')
        ->call('startWhatsappQrPairing');

    expect($component->get('whatsappQrChannelId'))->toBe('ch_new')
        ->and($component->get('whatsappQrText'))->toBe('RAW-QR-PAYLOAD')
        ->and($component->get('whatsappQrPolling'))->toBeTrue();

    $component->call('pollWhatsappQrStatus');

    expect($component->get('whatsappQrPolling'))->toBeFalse()
        ->and($component->get('whatsappQrStatus'))->toBe('connected')
        ->and($component->get('whatsappQrText'))->toBeNull();

    expect(app(Settings::class)->get('okta_channel_id'))->toBe('ch_new');
});

it('adopts the channel when the QR session reports disconnected but the channel list shows it connected', function () {
    (new NotificationTemplateSeeder)->run();
    asAdmin();

    bindFakeOktaGateway([
        // 1) QR session poll comes back terminal-but-not-connected...
        new Psr7Response(200, ['Content-Type' => 'application/json'], json_encode([
            'channel' => ['id' => 'ch_paired', 'display_name' => 'Almoosa WhatsApp', 'status' => 'disconnected'],
            'qr' => null,
            'qr_ttl_seconds' => null,
        ])),
        // 2) ...but the channel list (the Okta platform's own truth) shows
        //    the very same channel as connected.
        new Psr7Response(200, ['Content-Type' => 'application/json'], json_encode([
            'data' => [
                ['id' => 'ch_paired', 'display_name' => 'Almoosa WhatsApp', 'status' => 'connected'],
            ],
        ])),
    ]);

    $component = Livewire::test(Manage::class)
        ->set('oktaBaseUrl', 'https://connect.example.com')
        ->set('oktaTokenInput', 'a-token')
        ->set('whatsappQrChannelId', 'ch_paired')
        ->set('whatsappQrPolling', true)
        ->set('whatsappQrModalOpen', true)
        ->call('pollWhatsappQrStatus');

    expect($component->get('whatsappQrStatus'))->toBe('connected')
        ->and($component->get('whatsappQrModalOpen'))->toBeFalse()
        ->and($component->get('oktaChannelId'))->toBe('ch_paired');

    expect(app(Settings::class)->get('okta_channel_id'))->toBe('ch_paired');
});

it('shows the QR pairing section since the Okta driver always implements the pairing interface', function () {
    (new NotificationTemplateSeeder)->run();
    asAdmin();

    // The settings screen always builds a concrete OktaWhatsAppGateway for
    // this section (see Manage::buildOktaGateway()), and that class always
    // implements WhatsAppChannelPairingInterface — so the capability check
    // is expected to be true today. The `@else` branch in the view exists
    // for a hypothetical future driver that doesn't support QR pairing at
    // all (see WhatsAppChannelPairingInterface's docblock).
    Livewire::test(Manage::class)
        ->assertSee(__('notifications.settings.action_connect_whatsapp'))
        ->assertDontSee(__('notifications.settings.qr_not_supported'));
});

<?php

namespace App\Livewire\Settings\Notifications;

use App\Enums\MessageChannel;
use App\Enums\NotificationEvent;
use App\Models\NotificationTemplate;
use App\Services\Messaging\Contracts\WhatsAppChannelPairingInterface;
use App\Services\Messaging\Drivers\OktaWhatsAppGateway;
use App\Services\Messaging\Drivers\TaqnyatSmsGateway;
use App\Support\Settings;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Throwable;

/**
 * Notifications settings screen: per-event/per-channel message
 * templates, the SMS/WhatsApp channel toggles, the Taqnyat sender name +
 * API key, and the Okta Connect WhatsApp credentials + QR channel
 * pairing — all admin-editable at runtime.
 *
 * Provider credentials (Taqnyat API key, Okta token) are stored encrypted
 * via {@see Settings::setSecret()} and are *never* sent back to the
 * browser: their input properties below always start empty, and the
 * screen only ever displays a `hasKey`/masked-last-4 computed value. The
 * "verify connection" / "connect via QR" actions always talk to that
 * specific provider directly (bypassing the SMS_DRIVER/WHATSAPP_DRIVER
 * selection used for real sends), since this screen is about
 * administering *that* provider's own credentials regardless of which
 * driver is currently active for production sends.
 */
class Manage extends Component
{
    /** @var array<string, array<string, array{body: string, is_active: bool}>> */
    public array $templates = [];

    public ?string $senderName = null;

    public bool $smsEnabled = true;

    public bool $whatsappEnabled = false;

    /**
     * The editable body of the receipt-confirmation message (the one that
     * carries the signed link). Must always contain the {link} placeholder.
     */
    public string $confirmationBody = '';

    /**
     * Always starts empty — never pre-filled from the stored secret. A
     * non-empty value here means "replace the saved key on next save";
     * left empty, the currently-saved key (if any) is kept as-is.
     */
    public string $taqnyatApiKeyInput = '';

    public string $oktaBaseUrl = '';

    public string $oktaChannelId = '';

    /** Always starts empty — see {@see $taqnyatApiKeyInput}. */
    public string $oktaTokenInput = '';

    /** @var array{success: bool, message: ?string, balance: ?string}|null */
    public ?array $taqnyatVerifyResult = null;

    /**
     * Sender names fetched from the connected Taqnyat account (accepted
     * only — see {@see TaqnyatSmsGateway::normalizeSenders()}), populated
     * on demand by {@see fetchSenders()}. Empty/unfetched by default, in
     * which case the view keeps showing the free-text sender field only.
     *
     * @var list<array{name: string, status: ?string}>
     */
    public array $availableSenders = [];

    /** Whether {@see fetchSenders()} has been called at least once (even if it came back empty), so the view can tell "not fetched yet" apart from "fetched, none found". */
    public bool $sendersLoaded = false;

    /**
     * Bound to the sender pick-list. The sentinel value `__manual` means
     * "keep using the free-text {@see $senderName} field as-is" rather
     * than a fetched name.
     */
    public string $senderSelection = '__manual';

    /** @var array{success: bool, message: ?string, status: ?string}|null */
    public ?array $oktaVerifyResult = null;

    public ?string $whatsappQrChannelId = null;

    public ?string $whatsappQrText = null;

    public ?string $whatsappQrStatus = null;

    public bool $whatsappQrPolling = false;

    public ?string $whatsappQrMessage = null;

    public bool $whatsappQrModalOpen = false;

    /**
     * Existing Okta channels fetched on demand so the operator can link an
     * already-provisioned (often already-connected) channel instead of
     * pairing a new one via QR.
     *
     * @var array<int, array{id: string, name: ?string, status: ?string, type: ?string}>
     */
    public array $oktaChannels = [];

    public bool $oktaChannelsFetched = false;

    public ?string $oktaChannelsMessage = null;

    public function mount(): void
    {
        Gate::authorize('notifications.settings.manage');

        foreach (NotificationEvent::cases() as $event) {
            foreach (MessageChannel::cases() as $channel) {
                $template = NotificationTemplate::query()
                    ->where('event', $event->value)
                    ->where('channel', $channel->value)
                    ->first();

                $this->templates[$event->value][$channel->value] = [
                    'body' => $template?->body ?? '',
                    'is_active' => $template?->is_active ?? false,
                ];
            }
        }

        $settings = app(Settings::class);

        $this->senderName = $settings->get('taqnyat_sender') ?: null;
        $this->smsEnabled = $settings->get('sms_enabled', '1') === '1';
        $this->whatsappEnabled = $settings->get('whatsapp_enabled', '0') === '1';
        $this->confirmationBody = $settings->get('confirmation_body') ?: __('confirmations.default_body');

        // Non-secret provider fields are shown as-is (the saved override,
        // or blank to fall back to config/.env) — unlike the secret
        // fields above, there is nothing sensitive about a base URL or a
        // channel id.
        $this->oktaBaseUrl = $settings->get('okta_base_url') ?: '';
        $this->oktaChannelId = $settings->get('okta_channel_id') ?: '';
    }

    public function save(): void
    {
        Gate::authorize('notifications.settings.manage');

        $this->validate([
            'templates.*.*.body' => ['required', 'string', 'max:480'],
            'templates.*.*.is_active' => ['boolean'],
            'senderName' => ['nullable', 'string', 'max:11'],
            'smsEnabled' => ['boolean'],
            'whatsappEnabled' => ['boolean'],
            // The confirmation message must always keep the {link} placeholder,
            // otherwise the beneficiary would get a message with no way to
            // confirm receipt.
            'confirmationBody' => ['required', 'string', 'max:480', 'regex:/\{link\}/'],
            'taqnyatApiKeyInput' => ['nullable', 'string', 'max:255'],
            'oktaBaseUrl' => ['nullable', 'string', 'max:255'],
            'oktaChannelId' => ['nullable', 'string', 'max:255'],
            'oktaTokenInput' => ['nullable', 'string', 'max:255'],
        ], [
            'confirmationBody.regex' => __('notifications.settings.confirmation_body_link_required'),
        ]);

        foreach ($this->templates as $eventValue => $channels) {
            foreach ($channels as $channelValue => $data) {
                NotificationTemplate::query()->updateOrCreate(
                    ['event' => $eventValue, 'channel' => $channelValue],
                    ['body' => $data['body'], 'is_active' => (bool) ($data['is_active'] ?? false)],
                );
            }
        }

        $settings = app(Settings::class);

        $settings->set('taqnyat_sender', $this->senderName !== null ? trim($this->senderName) : null);
        $settings->set('sms_enabled', $this->smsEnabled ? '1' : '0');
        $settings->set('whatsapp_enabled', $this->whatsappEnabled ? '1' : '0');
        $settings->set('confirmation_body', trim($this->confirmationBody));

        $settings->set('okta_base_url', trim($this->oktaBaseUrl) !== '' ? trim($this->oktaBaseUrl) : null);
        $settings->set('okta_channel_id', trim($this->oktaChannelId) !== '' ? trim($this->oktaChannelId) : null);

        // An empty input means "keep the currently-saved secret" — only
        // overwrite when the admin actually typed something.
        if (trim($this->taqnyatApiKeyInput) !== '') {
            $settings->setSecret('taqnyat_api_key', trim($this->taqnyatApiKeyInput));
        }

        if (trim($this->oktaTokenInput) !== '') {
            $settings->setSecret('okta_token', trim($this->oktaTokenInput));
        }

        // Never leave a just-typed secret sitting in component state once
        // it's persisted.
        $this->taqnyatApiKeyInput = '';
        $this->oktaTokenInput = '';

        // Stale verify/QR state referred to the previous credentials.
        $this->taqnyatVerifyResult = null;
        $this->oktaVerifyResult = null;
        $this->resetWhatsappQrState();

        $this->dispatch('toast', type: 'success', message: __('notifications.settings.saved'));
    }

    public function clearTaqnyatApiKey(): void
    {
        Gate::authorize('notifications.settings.manage');

        app(Settings::class)->setSecret('taqnyat_api_key', null);
        $this->taqnyatApiKeyInput = '';
        $this->taqnyatVerifyResult = null;

        $this->dispatch('toast', type: 'success', message: __('notifications.settings.taqnyat_key_cleared'));
    }

    public function clearOktaToken(): void
    {
        Gate::authorize('notifications.settings.manage');

        app(Settings::class)->setSecret('okta_token', null);
        $this->oktaTokenInput = '';
        $this->oktaVerifyResult = null;

        $this->dispatch('toast', type: 'success', message: __('notifications.settings.okta_token_cleared'));
    }

    /**
     * Unlinks a stale/incorrect channel id (e.g. a leftover value from a
     * time when the field was hand-editable) without touching the base
     * URL or token. The operator re-links via QR pairing afterwards.
     */
    public function clearOktaChannel(): void
    {
        Gate::authorize('notifications.settings.manage');

        app(Settings::class)->set('okta_channel_id', null);
        $this->oktaChannelId = '';
        $this->oktaVerifyResult = null;

        $this->dispatch('toast', type: 'success', message: __('notifications.settings.okta_channel_cleared'));
    }

    #[Computed]
    public function taqnyatHasKey(): bool
    {
        return app(Settings::class)->getSecret('taqnyat_api_key') !== null;
    }

    #[Computed]
    public function taqnyatKeyMasked(): ?string
    {
        return app(Settings::class)->maskedSecret('taqnyat_api_key');
    }

    #[Computed]
    public function oktaHasToken(): bool
    {
        return app(Settings::class)->getSecret('okta_token') !== null;
    }

    #[Computed]
    public function oktaTokenMasked(): ?string
    {
        return app(Settings::class)->maskedSecret('okta_token');
    }

    /**
     * Whether the Okta driver (always the concrete class used by this
     * screen's Okta section — see class docblock) supports QR channel
     * pairing. Probed via `instanceof` per
     * {@see WhatsAppChannelPairingInterface}'s contract, rather than
     * assumed.
     */
    #[Computed]
    public function whatsappPairingSupported(): bool
    {
        return $this->buildOktaGateway($this->effectiveOktaConfig()) instanceof WhatsAppChannelPairingInterface;
    }

    /**
     * Whether enough Okta config exists (base URL + token) to make calls —
     * used to auto-load the channel list on page open without prompting.
     */
    #[Computed]
    public function oktaConfigured(): bool
    {
        $config = $this->effectiveOktaConfig();

        return $config['baseUrl'] !== '' && $config['token'] !== '';
    }

    /**
     * The status of the channel currently in use (okta_channel_id), looked
     * up in the fetched channel list — null when no channel is set, the
     * list has not been fetched, or the channel is not among the account's
     * channels (itself a disconnect signal).
     */
    #[Computed]
    public function activeChannelStatus(): ?string
    {
        $active = trim($this->oktaChannelId);

        if ($active === '') {
            return null;
        }

        foreach ($this->oktaChannels as $channel) {
            if ((string) $channel['id'] === $active) {
                return $channel['status'];
            }
        }

        return null;
    }

    /**
     * True when the channel in use is known to be not connected — so the
     * screen can warn that outgoing WhatsApp messages may not be delivered.
     */
    #[Computed]
    public function activeChannelDisconnected(): bool
    {
        return $this->oktaChannelsFetched
            && $this->oktaChannelsMessage === null
            && trim($this->oktaChannelId) !== ''
            && $this->activeChannelStatus() !== 'connected';
    }

    /**
     * Tests the *currently effective* Taqnyat API key: whatever is typed
     * (but not yet saved) in {@see $taqnyatApiKeyInput}, else the saved
     * secret, else the config/.env default.
     */
    public function verifyTaqnyat(): void
    {
        Gate::authorize('notifications.settings.manage');

        $settings = app(Settings::class);
        $typed = trim($this->taqnyatApiKeyInput);
        $apiKey = $typed !== '' ? $typed : ($settings->getSecret('taqnyat_api_key') ?: (string) config('services.taqnyat.api_key'));
        $sender = $settings->get('taqnyat_sender') ?: (string) config('services.taqnyat.sender');

        if ($apiKey === '') {
            $this->taqnyatVerifyResult = [
                'success' => false,
                'message' => __('notifications.settings.taqnyat_verify_missing_config'),
                'balance' => null,
            ];

            return;
        }

        $response = (new TaqnyatSmsGateway(apiKey: $apiKey, sender: $sender))->verify();

        $balance = $response->raw['balance'] ?? null;

        $this->taqnyatVerifyResult = [
            'success' => $response->success,
            'message' => $response->error,
            'balance' => is_scalar($balance) ? (string) $balance : null,
        ];
    }

    /**
     * Pulls the accepted sender names off the currently effective Taqnyat
     * account (same "typed-but-unsaved, else saved, else .env" precedence
     * as {@see verifyTaqnyat()}) so the sender field can offer a pick-list
     * instead of pure free text.
     */
    public function fetchSenders(): void
    {
        Gate::authorize('notifications.settings.manage');

        $settings = app(Settings::class);
        $typed = trim($this->taqnyatApiKeyInput);
        $apiKey = $typed !== '' ? $typed : ($settings->getSecret('taqnyat_api_key') ?: (string) config('services.taqnyat.api_key'));
        $sender = $settings->get('taqnyat_sender') ?: (string) config('services.taqnyat.sender');

        if ($apiKey === '') {
            $this->dispatch('toast', type: 'error', message: __('notifications.settings.taqnyat_verify_missing_config'));

            return;
        }

        $response = (new TaqnyatSmsGateway(apiKey: $apiKey, sender: $sender))->senders();

        if (! $response->success) {
            $this->dispatch('toast', type: 'error', message: __('notifications.settings.senders_fetch_failed', ['message' => $response->error ?? '']));

            return;
        }

        /** @var list<array{name: string, status: ?string}> $normalized */
        $normalized = $response->raw['normalizedSenders'] ?? [];

        $this->availableSenders = $normalized;
        $this->sendersLoaded = true;
        $this->senderSelection = '__manual';

        if ($normalized === []) {
            $this->dispatch('toast', type: 'error', message: __('notifications.settings.senders_fetch_empty'));

            return;
        }

        $this->dispatch('toast', type: 'success', message: __('notifications.settings.senders_fetch_success'));
    }

    /**
     * Picking a fetched sender name fills the free-text {@see $senderName}
     * field with it; picking the "manual entry" sentinel leaves whatever
     * is already typed there untouched.
     */
    public function updatedSenderSelection(string $value): void
    {
        if ($value !== '__manual' && $value !== '') {
            $this->senderName = $value;
        }
    }

    public function verifyOkta(): void
    {
        Gate::authorize('notifications.settings.manage');

        $config = $this->effectiveOktaConfig();

        if ($config['baseUrl'] === '' || $config['token'] === '' || $config['channelId'] === '') {
            $this->oktaVerifyResult = [
                'success' => false,
                'message' => __('notifications.settings.okta_verify_missing_config'),
                'status' => null,
            ];

            return;
        }

        $response = $this->buildOktaGateway($config)->verify();
        $status = $response->raw['status'] ?? null;

        $this->oktaVerifyResult = [
            'success' => $response->success,
            'message' => $response->error,
            'status' => is_scalar($status) ? (string) $status : null,
        ];
    }

    public function startWhatsappQrPairing(): void
    {
        $this->whatsappQrModalOpen = true;

        Gate::authorize('notifications.settings.manage');

        $config = $this->effectiveOktaConfig();

        if (! $this->whatsappPairingSupported) {
            return;
        }

        if ($config['baseUrl'] === '' || $config['token'] === '') {
            $this->whatsappQrMessage = __('notifications.settings.okta_qr_missing_credentials');

            return;
        }

        try {
            $session = $this->buildOktaGateway($config)->startQrPairing(
                config('app.name', 'Almoosa Charity').' WhatsApp',
            );
        } catch (Throwable $e) {
            $this->whatsappQrMessage = $e->getMessage();

            return;
        }

        $this->whatsappQrChannelId = $session->channelId;
        $this->whatsappQrText = $session->qr;
        $this->whatsappQrStatus = $session->status;
        $this->whatsappQrPolling = ! $session->isTerminal();
        $this->whatsappQrMessage = null;

        // The session can come back immediately terminal (e.g. the channel
        // was already paired on the Okta platform) — reconcile against the
        // real channel status before showing a "disconnected" dead end.
        if ($session->isTerminal() && ! $session->isConnected()) {
            $this->adoptChannelIfConnected($session->channelId);

            return;
        }

        $this->dispatch('whatsapp-qr-updated', text: $this->whatsappQrText);
    }

    /**
     * Closes the QR pairing modal (via its explicit close button), clearing
     * any in-progress pairing state so a later re-open starts fresh.
     */
    public function closeWhatsappQrPairing(): void
    {
        Gate::authorize('notifications.settings.manage');

        $this->resetWhatsappQrState();
        $this->whatsappQrModalOpen = false;

        $this->dispatch('whatsapp-qr-updated', text: null);
    }

    public function pollWhatsappQrStatus(): void
    {
        Gate::authorize('notifications.settings.manage');

        if (! $this->whatsappQrPolling || $this->whatsappQrChannelId === null) {
            return;
        }

        try {
            $session = $this->buildOktaGateway($this->effectiveOktaConfig())
                ->qrPairingStatus($this->whatsappQrChannelId);
        } catch (Throwable $e) {
            $this->whatsappQrPolling = false;
            $this->whatsappQrMessage = $e->getMessage();
            $this->dispatch('whatsapp-qr-updated', text: null);

            return;
        }

        $this->whatsappQrStatus = $session->status;
        $this->whatsappQrText = $session->qr;

        if ($session->isConnected()) {
            $this->markChannelConnected($session->channelId);

            return;
        }

        if ($session->isTerminal()) {
            // The QR *session* just ended — but the channel itself may have
            // paired successfully (it shows as connected on the Okta
            // platform). Reconcile against the channel list before treating
            // this as a failure.
            if ($this->adoptChannelIfConnected($this->whatsappQrChannelId)) {
                return;
            }

            $this->whatsappQrPolling = false;
            $this->dispatch('whatsapp-qr-updated', text: null);
            $this->dispatch('toast', type: 'error', message: __('notifications.settings.okta_qr_failed'));

            return;
        }

        $this->dispatch('whatsapp-qr-updated', text: $this->whatsappQrText);
    }

    /**
     * Adopt the pairing channel as the active one when the channel list —
     * the same source of truth the operator sees on the Okta platform —
     * reports it as connected, even if the QR *session* endpoint reported a
     * terminal 'disconnected'/'failed'. Returns true when adopted.
     */
    protected function adoptChannelIfConnected(?string $channelId): bool
    {
        if ($channelId === null || $channelId === '') {
            return false;
        }

        try {
            $channels = $this->buildOktaGateway($this->effectiveOktaConfig())->listChannels();
        } catch (Throwable) {
            return false;
        }

        foreach ($channels as $channel) {
            if ((string) $channel['id'] === (string) $channelId && $channel['status'] === 'connected') {
                $this->markChannelConnected($channelId);

                return true;
            }
        }

        return false;
    }

    /**
     * Persist the just-paired channel as the active okta_channel_id, close
     * the QR modal, and confirm to the operator. Shared by the direct
     * "session connected" path and the channel-list reconciliation above.
     */
    protected function markChannelConnected(string $channelId): void
    {
        $this->whatsappQrPolling = false;
        $this->whatsappQrStatus = 'connected';
        $this->whatsappQrModalOpen = false;
        $this->dispatch('whatsapp-qr-updated', text: null);

        app(Settings::class)->set('okta_channel_id', $channelId);
        $this->oktaChannelId = $channelId;

        $this->dispatch('toast', type: 'success', message: __('notifications.settings.okta_qr_connected'));
    }

    protected function resetWhatsappQrState(): void
    {
        $this->whatsappQrChannelId = null;
        $this->whatsappQrText = null;
        $this->whatsappQrStatus = null;
        $this->whatsappQrPolling = false;
        $this->whatsappQrMessage = null;
    }

    /**
     * Fetch the channels already provisioned on the Okta account so the
     * operator can link an existing (often already-connected) one rather
     * than pairing a new channel via QR.
     */
    public function fetchOktaChannels(): void
    {
        Gate::authorize('notifications.settings.manage');

        $this->oktaChannelsMessage = null;

        $config = $this->effectiveOktaConfig();

        if ($config['baseUrl'] === '' || $config['token'] === '') {
            $this->oktaChannelsMessage = __('notifications.settings.okta_channels_missing_credentials');

            return;
        }

        $gateway = $this->buildOktaGateway($config);

        if (! $gateway instanceof WhatsAppChannelPairingInterface) {
            return;
        }

        try {
            $this->oktaChannels = $gateway->listChannels();
        } catch (Throwable $e) {
            $this->oktaChannels = [];
            $this->oktaChannelsFetched = true;
            $this->oktaChannelsMessage = $e->getMessage();

            return;
        }

        $this->oktaChannelsFetched = true;
    }

    /**
     * Link an existing channel by persisting its id as the active
     * okta_channel_id (used for real sends).
     */
    public function useOktaChannel(string $channelId): void
    {
        Gate::authorize('notifications.settings.manage');

        $channelId = trim($channelId);

        if ($channelId === '') {
            return;
        }

        app(Settings::class)->set('okta_channel_id', $channelId);
        $this->oktaChannelId = $channelId;
        $this->oktaVerifyResult = null;

        $this->dispatch('toast', type: 'success', message: __('notifications.settings.okta_channel_selected'));
    }

    /**
     * @return array{baseUrl: string, token: string, channelId: string}
     */
    protected function effectiveOktaConfig(): array
    {
        $settings = app(Settings::class);
        $typedToken = trim($this->oktaTokenInput);

        return [
            'baseUrl' => trim($this->oktaBaseUrl) !== '' ? trim($this->oktaBaseUrl) : (string) config('services.okta_connect.base_url'),
            'token' => $typedToken !== '' ? $typedToken : ($settings->getSecret('okta_token') ?: (string) config('services.okta_connect.token')),
            'channelId' => trim($this->oktaChannelId) !== '' ? trim($this->oktaChannelId) : (string) config('services.okta_connect.channel_id'),
        ];
    }

    /**
     * Resolved through the container (rather than a bare `new`) so tests
     * can bind `OktaWhatsAppGateway::class` to a factory that injects a
     * mocked SDK HTTP transport — the vendor SDK talks to Guzzle directly
     * and isn't reachable through `Http::fake()`.
     *
     * @param  array{baseUrl: string, token: string, channelId: string}  $config
     */
    protected function buildOktaGateway(array $config): OktaWhatsAppGateway
    {
        return app()->make(OktaWhatsAppGateway::class, [
            'baseUrl' => $config['baseUrl'],
            'token' => $config['token'],
            'channelId' => $config['channelId'],
        ]);
    }

    public function render()
    {
        return view('livewire.settings.notifications.manage');
    }
}

@php
    $events = \App\Enums\NotificationEvent::cases();
    $channels = \App\Enums\MessageChannel::cases();
@endphp

<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ __('notifications.settings.title') }}</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('notifications.settings.subtitle') }}</p>
    </div>

    <form wire:submit="save" class="space-y-6">
        <x-ui.card>
            <x-slot:header>
                <div>
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('notifications.settings.section_templates_title') }}</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('notifications.settings.section_templates_description') }}</p>
                </div>
            </x-slot:header>

            <div class="mb-5 rounded-(--radius-brand) border border-dashed border-gray-200 p-4 dark:border-white/10">
                <p class="mb-1.5 text-xs font-semibold text-gray-700 dark:text-gray-200">{{ __('notifications.settings.legend_title') }}</p>
                <ul class="space-y-1 text-xs text-gray-500 dark:text-gray-400">
                    <li>{{ __('notifications.settings.legend_name') }}</li>
                    <li>{{ __('notifications.settings.legend_amount') }}</li>
                    <li>{{ __('notifications.settings.legend_program') }}</li>
                </ul>
            </div>

            <div class="space-y-6">
                @foreach ($events as $event)
                    <div class="rounded-(--radius-brand) border border-gray-200 p-4 dark:border-white/10">
                        <h3 class="mb-4 text-sm font-semibold text-gray-900 dark:text-white">{{ $event->label() }}</h3>

                        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                            @foreach ($channels as $channel)
                                <div class="space-y-2">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('notifications.channels.'.$channel->value) }}</span>

                                        <label class="flex cursor-pointer items-center gap-2 select-none">
                                            <span class="relative inline-block h-5 w-9 shrink-0">
                                                <input
                                                    type="checkbox"
                                                    wire:model="templates.{{ $event->value }}.{{ $channel->value }}.is_active"
                                                    class="peer sr-only"
                                                />
                                                <span class="absolute inset-0 rounded-full bg-gray-200 transition-colors duration-200 ease-out peer-checked:bg-primary dark:bg-white/10"></span>
                                                <span class="absolute start-0.5 top-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform duration-200 ease-out peer-checked:translate-x-4 rtl:peer-checked:-translate-x-4"></span>
                                            </span>
                                            <span class="text-xs text-gray-600 dark:text-gray-300">{{ __('notifications.settings.field_is_active') }}</span>
                                        </label>
                                    </div>

                                    <textarea
                                        wire:model="templates.{{ $event->value }}.{{ $channel->value }}.body"
                                        rows="3"
                                        maxlength="480"
                                        class="block w-full rounded-(--radius-brand) border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 shadow-sm transition duration-200 ease-out focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30 dark:border-white/10 dark:bg-primary-950/30 dark:text-gray-100"
                                    ></textarea>

                                    @error('templates.'.$event->value.'.'.$channel->value.'.body')
                                        <p class="text-xs text-status-rejected">{{ $message }}</p>
                                    @enderror
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </x-ui.card>

        <x-ui.card>
            <x-slot:header>
                <div>
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('notifications.settings.section_channels_title') }}</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('notifications.settings.section_channels_description') }}</p>
                </div>
            </x-slot:header>

            <div class="flex flex-col gap-4 sm:flex-row sm:gap-8">
                <label class="flex cursor-pointer items-center gap-3 select-none">
                    <span class="relative inline-block h-6 w-11 shrink-0">
                        <input type="checkbox" wire:model="smsEnabled" class="peer sr-only" />
                        <span class="absolute inset-0 rounded-full bg-gray-200 transition-colors duration-200 ease-out peer-checked:bg-primary dark:bg-white/10"></span>
                        <span class="absolute start-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow transition-transform duration-200 ease-out peer-checked:translate-x-5 rtl:peer-checked:-translate-x-5"></span>
                    </span>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('notifications.settings.field_sms_enabled') }}</span>
                </label>

                <label class="flex cursor-pointer items-center gap-3 select-none">
                    <span class="relative inline-block h-6 w-11 shrink-0">
                        <input type="checkbox" wire:model="whatsappEnabled" class="peer sr-only" />
                        <span class="absolute inset-0 rounded-full bg-gray-200 transition-colors duration-200 ease-out peer-checked:bg-primary dark:bg-white/10"></span>
                        <span class="absolute start-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow transition-transform duration-200 ease-out peer-checked:translate-x-5 rtl:peer-checked:-translate-x-5"></span>
                    </span>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('notifications.settings.field_whatsapp_enabled') }}</span>
                </label>
            </div>
        </x-ui.card>

        <x-ui.card>
            <x-slot:header>
                <div>
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('notifications.settings.section_taqnyat_title') }}</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('notifications.settings.section_taqnyat_description') }}</p>
                </div>
            </x-slot:header>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <x-ui.input
                        :label="__('notifications.settings.field_sender_name')"
                        name="senderName"
                        wire:model="senderName"
                        maxlength="11"
                        :hint="__('notifications.settings.field_sender_name_hint')"
                    />

                    <div class="mt-3 flex flex-wrap items-center gap-3">
                        <x-ui.button type="button" variant="ghost" size="sm" wire:click="fetchSenders">
                            {{ __('notifications.settings.action_fetch_senders') }}
                        </x-ui.button>
                    </div>

                    @if ($sendersLoaded)
                        @if (count($availableSenders) > 0)
                            <div class="mt-3">
                                <x-ui.select
                                    :label="__('notifications.settings.field_sender_select_label')"
                                    name="senderSelection"
                                    wire:model="senderSelection"
                                >
                                    <option value="__manual">{{ __('notifications.settings.field_sender_manual_option') }}</option>
                                    @foreach ($availableSenders as $sender)
                                        <option value="{{ $sender['name'] }}">{{ $sender['name'] }}</option>
                                    @endforeach
                                </x-ui.select>

                                <div class="mt-2 flex flex-wrap items-center gap-1.5">
                                    @foreach ($availableSenders as $sender)
                                        <x-ui.badge color="approved">
                                            {{ $sender['name'] }}
                                            @if ($sender['status'])
                                                — {{ __('notifications.settings.sender_status_'.$sender['status']) }}
                                            @endif
                                        </x-ui.badge>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ __('notifications.settings.senders_fetch_empty') }}</p>
                        @endif
                    @endif
                </div>

                <div>
                    <x-ui.input
                        :label="__('notifications.settings.field_api_key')"
                        name="taqnyatApiKeyInput"
                        type="password"
                        autocomplete="off"
                        wire:model="taqnyatApiKeyInput"
                        maxlength="255"
                        dir="ltr"
                        :placeholder="$this->taqnyatHasKey ? $this->taqnyatKeyMasked : __('notifications.settings.field_api_key_placeholder')"
                        :hint="__('notifications.settings.field_api_key_hint')"
                    />

                    @if ($this->taqnyatHasKey)
                        <button
                            type="button"
                            wire:click="clearTaqnyatApiKey"
                            wire:confirm="{{ __('notifications.settings.confirm_clear_secret') }}"
                            class="mt-1.5 text-xs font-medium text-status-rejected hover:underline"
                        >
                            {{ __('notifications.settings.action_clear') }}
                        </button>
                    @endif
                </div>
            </div>

            <div class="mt-5 flex flex-wrap items-center gap-3">
                <x-ui.button type="button" variant="ghost" size="sm" wire:click="verifyTaqnyat">
                    {{ __('notifications.settings.action_verify') }}
                </x-ui.button>

                @if ($taqnyatVerifyResult)
                    @if ($taqnyatVerifyResult['success'])
                        <x-ui.badge color="approved">
                            {{ $taqnyatVerifyResult['balance']
                                ? __('notifications.settings.verify_success_with_balance', ['balance' => $taqnyatVerifyResult['balance']])
                                : __('notifications.settings.verify_success') }}
                        </x-ui.badge>
                    @else
                        <x-ui.badge color="rejected">
                            {{ __('notifications.settings.verify_failed', ['message' => $taqnyatVerifyResult['message'] ?? '']) }}
                        </x-ui.badge>
                    @endif
                @endif
            </div>
        </x-ui.card>

        <x-ui.card>
            <x-slot:header>
                <div>
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('notifications.settings.section_okta_title') }}</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('notifications.settings.section_okta_description') }}</p>
                </div>
            </x-slot:header>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <x-ui.input
                    :label="__('notifications.settings.field_okta_base_url')"
                    name="oktaBaseUrl"
                    wire:model="oktaBaseUrl"
                    maxlength="255"
                    dir="ltr"
                    :hint="__('notifications.settings.field_okta_base_url_hint')"
                />

                <div>
                    <x-ui.input
                        :label="__('notifications.settings.field_okta_token')"
                        name="oktaTokenInput"
                        type="password"
                        autocomplete="off"
                        wire:model="oktaTokenInput"
                        maxlength="255"
                        dir="ltr"
                        :placeholder="$this->oktaHasToken ? $this->oktaTokenMasked : __('notifications.settings.field_okta_token_placeholder')"
                        :hint="__('notifications.settings.field_okta_token_hint')"
                    />

                    @if ($this->oktaHasToken)
                        <button
                            type="button"
                            wire:click="clearOktaToken"
                            wire:confirm="{{ __('notifications.settings.confirm_clear_secret') }}"
                            class="mt-1.5 text-xs font-medium text-status-rejected hover:underline"
                        >
                            {{ __('notifications.settings.action_clear') }}
                        </button>
                    @endif
                </div>
            </div>

            <div class="mt-5 flex flex-wrap items-center gap-3">
                <x-ui.button type="button" variant="ghost" size="sm" wire:click="verifyOkta">
                    {{ __('notifications.settings.action_verify') }}
                </x-ui.button>

                @if ($oktaVerifyResult)
                    @if ($oktaVerifyResult['success'])
                        <x-ui.badge color="approved">
                            {{ $oktaVerifyResult['status']
                                ? __('notifications.settings.okta_verify_status', ['status' => $oktaVerifyResult['status']])
                                : __('notifications.settings.verify_success') }}
                        </x-ui.badge>
                    @else
                        <x-ui.badge color="rejected">
                            {{ __('notifications.settings.verify_failed', ['message' => $oktaVerifyResult['message'] ?? '']) }}
                        </x-ui.badge>
                    @endif
                @endif
            </div>

            <div class="mt-6 border-t border-gray-200 pt-5 dark:border-white/10">
                <h3 class="mb-1 text-sm font-semibold text-gray-900 dark:text-white">{{ __('notifications.settings.section_okta_qr_title') }}</h3>

                @if ($this->whatsappPairingSupported)
                    <p class="mb-3 text-xs text-gray-500 dark:text-gray-400">{{ __('notifications.settings.qr_scanning_hint') }}</p>

                    <div class="flex flex-wrap items-center gap-3">
                        <x-ui.button type="button" variant="secondary" size="sm" wire:click="startWhatsappQrPairing">
                            {{ __('notifications.settings.action_connect_whatsapp') }}
                        </x-ui.button>

                        @if ($whatsappQrStatus)
                            <x-ui.badge :color="$whatsappQrStatus === 'connected' ? 'approved' : ($whatsappQrStatus === 'pending' ? 'review' : 'rejected')">
                                {{ __('notifications.settings.qr_status_'.$whatsappQrStatus) }}
                            </x-ui.badge>
                        @endif
                    </div>

                    {{-- The Channel ID is not entered by hand: it is produced by a
                         successful QR pairing (or taken from .env). Shown read-only
                         so the operator sees which channel is linked. --}}
                    <div class="mt-5 max-w-md">
                        <label for="oktaChannelId" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">
                            {{ __('notifications.settings.field_okta_channel_id') }}
                        </label>

                        @if (filled($oktaChannelId))
                            <div class="flex items-center gap-2">
                                <input
                                    id="oktaChannelId"
                                    type="text"
                                    readonly
                                    dir="ltr"
                                    value="{{ $oktaChannelId }}"
                                    class="block w-full rounded-(--radius-brand) border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-700 shadow-sm dark:border-white/10 dark:bg-white/5 dark:text-gray-200"
                                />
                                <x-ui.badge color="approved">{{ __('notifications.settings.okta_channel_linked') }}</x-ui.badge>
                                <button
                                    type="button"
                                    wire:click="clearOktaChannel"
                                    wire:confirm="{{ __('notifications.settings.okta_channel_unlink_confirm') }}"
                                    class="shrink-0 text-xs font-medium text-status-rejected hover:underline"
                                >
                                    {{ __('notifications.settings.okta_channel_unlink') }}
                                </button>
                            </div>
                        @else
                            <div class="rounded-(--radius-brand) border border-dashed border-gray-300 bg-gray-50/60 px-3.5 py-3 text-sm text-gray-500 dark:border-white/10 dark:bg-white/5 dark:text-gray-400">
                                {{ __('notifications.settings.okta_channel_not_linked') }}
                            </div>
                        @endif

                        <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">{{ __('notifications.settings.field_okta_channel_id_hint') }}</p>
                    </div>
                @else
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('notifications.settings.qr_not_supported') }}</p>
                @endif
            </div>
        </x-ui.card>

        <div class="flex items-center justify-end gap-3">
            <x-ui.button type="submit" variant="primary" wire:target="save">
                {{ __('common.save') }}
            </x-ui.button>
        </div>
    </form>

    @if ($whatsappQrModalOpen)
        <div class="fixed inset-0 z-[70] overflow-y-auto" role="dialog" aria-modal="true">
            <div class="absolute inset-0 bg-primary-950/60 backdrop-blur-sm" wire:click="closeWhatsappQrPairing"></div>

            <div class="flex min-h-dvh items-center justify-center px-4 py-6 text-center sm:p-0">
                <div class="relative my-8 inline-block w-full max-w-sm transform overflow-hidden rounded-(--radius-brand) bg-white p-6 text-start align-middle shadow-xl transition-all dark:bg-primary-950">
                    <h3 class="mb-1 text-sm font-semibold text-gray-900 dark:text-white">{{ __('notifications.settings.section_okta_qr_title') }}</h3>

                    <div class="mt-3 flex flex-wrap items-center gap-3">
                        @if ($whatsappQrStatus)
                            <x-ui.badge :color="$whatsappQrStatus === 'connected' ? 'approved' : ($whatsappQrStatus === 'pending' ? 'review' : 'rejected')">
                                {{ __('notifications.settings.qr_status_'.$whatsappQrStatus) }}
                            </x-ui.badge>
                        @endif
                    </div>

                    <div
                        wire:key="whatsapp-qr-poll"
                        wire:poll.5s.keep-alive="pollWhatsappQrStatus"
                        class="mt-4 flex flex-col items-center gap-2"
                    >
                        <div id="whatsapp-qr-svg" wire:ignore class="h-48 w-48 rounded-(--radius-brand) border border-gray-200 p-4 [&_svg]:h-full [&_svg]:w-full dark:border-white/10"></div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('notifications.settings.qr_waiting') }}</p>
                    </div>

                    @if ($whatsappQrMessage)
                        <p class="mt-3 text-xs text-status-rejected">{{ $whatsappQrMessage }}</p>
                    @endif

                    <div class="mt-5 flex items-center justify-end">
                        <x-ui.button type="button" variant="ghost" size="sm" wire:click="closeWhatsappQrPairing">
                            {{ __('common.close') }}
                        </x-ui.button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <script src="{{ asset('vendor/qrcode-generator/qrcode.js') }}"></script>
    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('whatsapp-qr-updated', ({ text }) => {
                const el = document.getElementById('whatsapp-qr-svg');

                if (! el) {
                    return;
                }

                if (! text) {
                    el.innerHTML = '';

                    return;
                }

                const qr = qrcode(0, 'L');
                qr.addData(text);
                qr.make();

                el.innerHTML = qr.createSvgTag({ cellSize: 5, margin: 2 });
            });
        });
    </script>
</div>

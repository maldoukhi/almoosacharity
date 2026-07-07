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

            <div class="max-w-xs">
                <x-ui.input
                    :label="__('notifications.settings.field_sender_name')"
                    name="senderName"
                    wire:model="senderName"
                    maxlength="11"
                    :hint="__('notifications.settings.field_sender_name_hint')"
                />
            </div>
        </x-ui.card>

        <div class="flex items-center justify-end gap-3">
            <x-ui.button type="submit" variant="primary" wire:target="save">
                {{ __('common.save') }}
            </x-ui.button>
        </div>
    </form>
</div>

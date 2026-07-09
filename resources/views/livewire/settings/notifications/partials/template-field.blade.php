<div class="space-y-2" wire:key="tpl-{{ $event->value }}-{{ $channel->value }}">
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

    @if ($combinedDeliveryMessage && $event === \App\Enums\NotificationEvent::AidDelivered)
        <p class="text-xs text-secondary-700 dark:text-secondary-300">{{ __('notifications.settings.combined_template_link_hint') }}</p>
    @endif
</div>

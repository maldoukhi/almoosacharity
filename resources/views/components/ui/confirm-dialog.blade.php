{{-- Shared confirmation modal, placed once in the app layout. Opened from
     anywhere via the $confirm(message, onConfirm, { danger }) Alpine magic
     (see resources/js/app.js), replacing native wire:confirm dialogs. --}}
<div
    x-data="{
        open: false,
        message: '',
        confirmLabel: @js(__('common.confirm')),
        danger: false,
        cb: null,
        run() {
            const cb = this.cb;
            this.open = false;
            this.cb = null;
            if (typeof cb === 'function') cb();
        },
    }"
    x-on:ui-confirm.window="
        message = $event.detail.message;
        confirmLabel = $event.detail.confirmLabel || @js(__('common.confirm'));
        danger = !! $event.detail.danger;
        cb = $event.detail.onConfirm;
        open = true;
    "
    x-on:keydown.escape.window="open = false"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-[80] overflow-y-auto"
    role="dialog"
    aria-modal="true"
>
    <div class="absolute inset-0 bg-primary-950/60 backdrop-blur-sm" @click="open = false"></div>

    <div class="flex min-h-dvh items-center justify-center p-4">
        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-2 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            class="relative w-full max-w-md overflow-hidden rounded-(--radius-brand) bg-white shadow-xl dark:bg-primary-950 dark:ring-1 dark:ring-white/10"
        >
            <div class="flex items-start gap-3 px-6 pt-6">
                <span
                    class="flex size-10 shrink-0 items-center justify-center rounded-full"
                    :class="danger ? 'bg-status-rejected/10 text-status-rejected' : 'bg-primary-50 text-primary-600 dark:bg-primary-500/15 dark:text-primary-300'"
                >
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                    </svg>
                </span>
                <div class="pt-1">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('common.confirm_title') }}</h3>
                    <p class="mt-1 text-sm leading-6 text-gray-600 dark:text-gray-300" x-text="message"></p>
                </div>
            </div>

            <div class="mt-6 flex items-center justify-end gap-3 px-6 pb-5">
                <x-ui.button type="button" variant="ghost" @click="open = false">
                    {{ __('common.cancel') }}
                </x-ui.button>
                <x-ui.button type="button" variant="danger" x-show="danger" @click="run()" x-text="confirmLabel"></x-ui.button>
                <x-ui.button type="button" variant="primary" x-show="! danger" @click="run()" x-text="confirmLabel"></x-ui.button>
            </div>
        </div>
    </div>
</div>

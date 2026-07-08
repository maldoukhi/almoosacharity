<div class="p-6">
    <div class="mb-5 flex items-start gap-3">
        <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-status-rejected/10 text-status-rejected">
            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
            </svg>
        </div>
        <div>
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                {{ __('aids.cancel_modal.title') }}
            </h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                {{ __('aids.cancel_modal.subtitle') }}
            </p>
        </div>
    </div>

    <dl class="divide-y divide-gray-100 rounded-(--radius-brand) border border-gray-100 dark:divide-white/10 dark:border-white/10">
        <div class="flex items-center justify-between gap-3 px-4 py-2.5">
            <dt class="text-sm text-gray-500 dark:text-gray-400">{{ __('aids.field_reference') }}</dt>
            <dd class="text-sm font-medium tabular-nums text-gray-900 dark:text-white">{{ $aid->reference }}</dd>
        </div>
        <div class="flex items-center justify-between gap-3 px-4 py-2.5">
            <dt class="text-sm text-gray-500 dark:text-gray-400">{{ __('aids.field_beneficiary') }}</dt>
            <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ $aid->beneficiary?->full_name }}</dd>
        </div>
    </dl>

    <div class="mt-6 flex items-center justify-end gap-3 border-t border-gray-100 pt-5 dark:border-white/10">
        <x-ui.button type="button" variant="ghost" wire:click="closeModal">
            {{ __('common.back') }}
        </x-ui.button>

        <x-ui.button type="button" variant="danger" wire:click="confirm" wire:target="confirm">
            {{ __('aids.cancel_modal.confirm_button') }}
        </x-ui.button>
    </div>
</div>

{{--
    Rendered directly by the InvalidSignatureException handler (see
    bootstrap/app.php) for the public.confirm route: a genuinely
    time-expired signed link is rejected by the 'signed' middleware before
    the ConfirmReceipt Livewire component ever mounts, so this plain
    (non-Livewire) view is what a beneficiary actually sees in that case.
    Kept visually consistent with ConfirmReceipt's own "expired" state.
--}}
<x-layouts::public :title="__('confirmations.expired_title')">
    <x-ui.card class="text-center">
        <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-status-review/10 text-status-review">
            <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2m6-2a10 10 0 1 1-20 0 10 10 0 0 1 20 0Z" />
            </svg>
        </div>

        <h1 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('confirmations.expired_title') }}</h1>
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ __('confirmations.expired_description') }}</p>
    </x-ui.card>
</x-layouts::public>

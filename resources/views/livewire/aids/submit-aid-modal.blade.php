@php
    $stage = $this->firstStage;
    $recipients = $this->recipientCount;
@endphp

<div class="p-6">
    <div class="mb-5 flex items-start gap-3">
        <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-primary-50 text-primary-600 dark:bg-primary-500/15 dark:text-primary-300">
            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
        </div>
        <div>
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                {{ __('aids.submit_modal.title') }}
            </h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                {{ __('aids.submit_modal.subtitle') }}
            </p>
        </div>
    </div>

    {{-- Summary of the aid --}}
    <dl class="divide-y divide-gray-100 rounded-(--radius-brand) border border-gray-100 dark:divide-white/10 dark:border-white/10">
        <div class="flex items-center justify-between gap-3 px-4 py-2.5">
            <dt class="text-sm text-gray-500 dark:text-gray-400">{{ __('aids.field_reference') }}</dt>
            <dd class="text-sm font-medium tabular-nums text-gray-900 dark:text-white">{{ $aid->reference }}</dd>
        </div>
        <div class="flex items-center justify-between gap-3 px-4 py-2.5">
            <dt class="text-sm text-gray-500 dark:text-gray-400">{{ __('aids.field_beneficiary') }}</dt>
            <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ $aid->beneficiary?->full_name }}</dd>
        </div>
        @if ($aid->program)
            <div class="flex items-center justify-between gap-3 px-4 py-2.5">
                <dt class="text-sm text-gray-500 dark:text-gray-400">{{ __('aids.field_program') }}</dt>
                <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ $aid->program->name }}</dd>
            </div>
        @endif
        <div class="flex items-center justify-between gap-3 px-4 py-2.5">
            <dt class="text-sm text-gray-500 dark:text-gray-400">{{ __('aids.field_type') }}</dt>
            <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ $aid->type->label() }}</dd>
        </div>
        <div class="flex items-center justify-between gap-3 px-4 py-2.5">
            <dt class="text-sm text-gray-500 dark:text-gray-400">
                {{ $aid->type === \App\Enums\AidType::Cash ? __('aids.field_amount') : __('aids.field_items') }}
            </dt>
            <dd class="text-sm font-semibold tabular-nums text-gray-900 dark:text-white">{{ $this->amountSummary }}</dd>
        </div>
    </dl>

    {{-- What happens on submission: the notification preview --}}
    <div class="mt-5 rounded-(--radius-brand) border border-secondary-200 bg-secondary-50/60 p-4 dark:border-secondary-500/20 dark:bg-secondary-500/10">
        <h3 class="flex items-center gap-2 text-sm font-semibold text-gray-900 dark:text-white">
            <svg class="size-4 text-secondary-600 dark:text-secondary-300" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
            </svg>
            {{ __('aids.submit_modal.notify_title') }}
        </h3>

        @if ($stage)
            <p class="mt-2 text-sm leading-6 text-gray-700 dark:text-gray-200">
                {{ __('aids.submit_modal.notify_stage', ['stage' => $stage->name, 'role' => $this->reviewerRoleLabel]) }}
            </p>

            @if ($recipients > 0)
                <p class="mt-1 text-sm leading-6 text-gray-700 dark:text-gray-200">
                    {{ trans_choice('aids.submit_modal.notify_recipients', $recipients, ['count' => $recipients]) }}
                </p>
                <div class="mt-3 flex flex-wrap gap-2">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-white px-2.5 py-1 text-xs font-medium text-gray-700 shadow-sm dark:bg-primary-950/40 dark:text-gray-200">
                        <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                        </svg>
                        {{ __('aids.submit_modal.channel_bell') }}
                    </span>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-white px-2.5 py-1 text-xs font-medium text-gray-700 shadow-sm dark:bg-primary-950/40 dark:text-gray-200">
                        <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                        </svg>
                        {{ __('aids.submit_modal.channel_email') }}
                    </span>
                </div>
            @else
                <p class="mt-1 text-sm leading-6 text-status-review">
                    {{ __('aids.submit_modal.notify_none') }}
                </p>
            @endif
        @else
            <p class="mt-2 text-sm leading-6 text-status-rejected">
                {{ __('aids.submit_modal.no_flow') }}
            </p>
        @endif
    </div>

    <div class="mt-6 flex items-center justify-end gap-3 border-t border-gray-100 pt-5 dark:border-white/10">
        <x-ui.button type="button" variant="ghost" wire:click="closeModal">
            {{ __('common.cancel') }}
        </x-ui.button>

        <x-ui.button type="button" variant="primary" wire:click="confirm" wire:target="confirm" :disabled="$stage === null">
            {{ __('aids.submit_button') }}
        </x-ui.button>
    </div>
</div>

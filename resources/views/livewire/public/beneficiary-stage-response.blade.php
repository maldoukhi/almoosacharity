<div>
    @if ($view === 'already')
        <x-ui.card class="text-center">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-status-delivered/10 text-status-delivered">
                <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                </svg>
            </div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('beneficiary_stage.already_title') }}</h1>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ __('beneficiary_stage.already_description') }}</p>
        </x-ui.card>

    @elseif ($view === 'expired')
        <x-ui.card class="text-center">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-status-review/10 text-status-review">
                <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2m6-2a10 10 0 1 1-20 0 10 10 0 0 1 20 0Z" />
                </svg>
            </div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('beneficiary_stage.expired_title') }}</h1>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ __('beneficiary_stage.expired_description') }}</p>
        </x-ui.card>

    @elseif ($view === 'not_found')
        <x-ui.card class="text-center">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-status-rejected/10 text-status-rejected">
                <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                </svg>
            </div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('beneficiary_stage.not_found_title') }}</h1>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ __('beneficiary_stage.not_found_description') }}</p>
        </x-ui.card>

    @elseif ($view === 'respond')
        @php $inKindItems = $this->aid->type === \App\Enums\AidType::InKind ? $this->aid->items : collect(); @endphp
        <x-ui.card>
            <div class="mb-5 text-center">
                <p class="text-base font-semibold text-gray-900 dark:text-white">
                    {{ __('beneficiary_stage.greeting', ['name' => $this->aid->beneficiary?->first_name ?? '']) }}
                </p>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('beneficiary_stage.intro') }}</p>
            </div>

            <dl class="space-y-3 rounded-(--radius-brand) bg-gray-50 p-4 text-sm dark:bg-white/5">
                <div class="flex items-center justify-between gap-3">
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('beneficiary_stage.field_stage') }}</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $response->stage_name }}</dd>
                </div>

                <div class="flex items-center justify-between gap-3">
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('beneficiary_stage.field_program') }}</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $this->aid->program?->name ?? __('common.dash') }}</dd>
                </div>

                <div class="flex items-center justify-between gap-3">
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('beneficiary_stage.field_type') }}</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $this->aid->type->label() }}</dd>
                </div>

                @if ($inKindItems->isNotEmpty())
                    <div class="flex items-start justify-between gap-3">
                        <dt class="shrink-0 text-gray-500 dark:text-gray-400">{{ __('beneficiary_stage.field_items') }}</dt>
                        <dd class="text-end font-medium text-gray-900 dark:text-white">
                            {{ $inKindItems->pluck('name')->implode('، ') }}
                        </dd>
                    </div>
                @endif
            </dl>

            <div class="mt-5">
                <label for="stage-note" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('beneficiary_stage.note_label') }}</label>
                <textarea
                    id="stage-note"
                    wire:model="note"
                    rows="4"
                    placeholder="{{ __('beneficiary_stage.note_placeholder') }}"
                    class="block w-full rounded-(--radius-brand) border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30 dark:border-white/10 dark:bg-primary-950/30 dark:text-gray-100"
                ></textarea>
                @error('note')
                    <p class="mt-1.5 text-xs text-status-rejected">{{ $message }}</p>
                @enderror
            </div>

            <div class="mt-4">
                <label for="stage-document" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('beneficiary_stage.document_label') }}</label>
                <input
                    id="stage-document"
                    type="file"
                    wire:model="document"
                    accept=".pdf,.jpg,.jpeg,.png"
                    class="block w-full rounded-(--radius-brand) border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 shadow-sm file:me-3 file:rounded-(--radius-brand) file:border-0 file:bg-primary-50 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-primary-700 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30 dark:border-white/10 dark:bg-primary-950/30 dark:text-gray-100 dark:file:bg-primary-500/20 dark:file:text-primary-100"
                >
                <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">{{ __('beneficiary_stage.document_hint') }}</p>
                <div wire:loading wire:target="document" class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">{{ __('beneficiary_stage.uploading') }}</div>
                @error('document')
                    <p class="mt-1.5 text-xs text-status-rejected">{{ $message }}</p>
                @enderror
            </div>

            <x-ui.button
                type="button"
                variant="secondary"
                size="lg"
                class="mt-6 w-full"
                wire:click="submit"
                wire:target="submit, document"
                wire:loading.attr="disabled"
            >
                {{ __('beneficiary_stage.submit_button') }}
            </x-ui.button>
        </x-ui.card>

    @else
        {{-- 'success' --}}
        <x-ui.card class="text-center">
            <svg class="mx-auto mb-4 h-16 w-16 text-status-delivered" viewBox="0 0 52 52" fill="none" aria-hidden="true">
                <circle cx="26" cy="26" r="24" stroke="currentColor" stroke-width="2" />
                <path fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" d="M14 27l7 7 17-17" />
            </svg>

            <h1 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('beneficiary_stage.success_title') }}</h1>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ __('beneficiary_stage.success_description') }}</p>
        </x-ui.card>
    @endif
</div>

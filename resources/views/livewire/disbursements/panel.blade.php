@php
    $disbursement = $this->disbursement;

    $methodIcons = [
        \App\Enums\DisbursementMethod::BankTransfer->value => 'M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-9.75 2.25h16.5a1.5 1.5 0 0 0 1.5-1.5V8.25a1.5 1.5 0 0 0-1.5-1.5H3.75a1.5 1.5 0 0 0-1.5 1.5v9.75a1.5 1.5 0 0 0 1.5 1.5Z',
        \App\Enums\DisbursementMethod::OfficePickup->value => 'M2.25 21h19.5M4.5 3h15M4.5 3v18m15-18v18M9 8.25h1.5m-1.5 3h1.5m3-3h1.5m-1.5 3h1.5M9 21v-4.5a1.5 1.5 0 0 1 1.5-1.5h3a1.5 1.5 0 0 1 1.5 1.5V21',
        \App\Enums\DisbursementMethod::Courier->value => 'M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.25h5.638a1.5 1.5 0 0 1 1.409.978l1.599 4.328a1.5 1.5 0 0 1 .091.512v3.932a1.5 1.5 0 0 1-1.5 1.5H21M14.25 7.5v11.25m0-11.25H8.25m0 0H2.25v9.75c0 .621.504 1.125 1.125 1.125h.75',
        \App\Enums\DisbursementMethod::FieldHandover->value => 'M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.286Z',
    ];
@endphp

<x-ui.card>
    <x-slot:header>
        <div class="flex items-center justify-between gap-3">
            <h2 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('disbursements.panel_title') }}</h2>

            @if ($disbursement)
                <x-ui.badge :color="$disbursement->status->color()">{{ $disbursement->status->label() }}</x-ui.badge>
            @endif
        </div>
    </x-slot:header>

    @if ($aid->status === \App\Enums\AidStatus::Approved)
        {{-- Step 1: pick the disbursement method and start it. --}}
        <div class="space-y-5">
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('disbursements.select_method_hint') }}</p>

            @if (empty($this->availableMethods))
                <x-ui.empty-state :title="__('disbursements.no_methods_title')" :description="__('disbursements.no_methods_description')" />
            @else
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    @foreach ($this->availableMethods as $option)
                        <label
                            wire:key="method-{{ $option->value }}"
                            class="flex cursor-pointer items-start gap-3 rounded-(--radius-brand) border-2 px-4 py-3 transition duration-150 ease-out {{ $method === $option->value ? 'border-primary-500 bg-primary-50/60 dark:border-primary-500 dark:bg-primary-900/30' : 'border-gray-200 hover:border-primary-300 dark:border-white/10 dark:hover:border-primary-700' }}"
                        >
                            <input
                                type="radio"
                                wire:model.live="method"
                                value="{{ $option->value }}"
                                class="mt-1 h-4 w-4 shrink-0 border-gray-300 text-primary-600 focus:ring-primary-500"
                            />

                            <span class="flex items-start gap-2.5">
                                <svg class="mt-0.5 h-5 w-5 shrink-0 text-primary-600 dark:text-primary-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $methodIcons[$option->value] }}" />
                                </svg>

                                <span>
                                    <span class="block text-sm font-medium text-gray-900 dark:text-white">{{ $option->label() }}</span>

                                    @if ($option->requiresBankAccount() && $this->bankHint)
                                        <span class="mt-0.5 block font-mono text-xs tabular-nums text-gray-500 dark:text-gray-400" dir="ltr">
                                            {{ $this->bankHint }}
                                        </span>
                                    @endif
                                </span>
                            </span>
                        </label>
                    @endforeach
                </div>

                @error('method')
                    <p class="text-xs text-status-rejected">{{ $message }}</p>
                @enderror

                <x-ui.button
                    type="button"
                    variant="primary"
                    class="w-full"
                    wire:click="start"
                    wire:confirm="{{ __('disbursements.confirm_start') }}"
                >
                    {{ __('disbursements.start_button') }}
                </x-ui.button>
            @endif
        </div>
    @elseif ($aid->status === \App\Enums\AidStatus::InDisbursement && $disbursement)
        {{-- Step 2: record the actual hand-off. --}}
        <div class="space-y-4">
            <div class="flex items-start gap-3 rounded-(--radius-brand) bg-status-review/10 px-4 py-3 text-sm text-status-review">
                <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                </svg>
                <div>
                    <p class="font-medium">{{ $disbursement->method->label() }}</p>
                    <p class="mt-0.5">{{ __('disbursements.proof_recommended_notice') }}</p>
                </div>
            </div>

            <x-ui.input
                :label="__($disbursement->method->referenceLabelKey())"
                name="reference"
                wire:model="reference"
            />

            @if ($disbursement->method === \App\Enums\DisbursementMethod::Courier)
                <x-ui.input
                    :label="__('disbursements.field_courier_name')"
                    name="courierName"
                    wire:model="courierName"
                />
            @endif

            <x-ui.input
                type="date"
                :label="__('disbursements.field_delivered_at')"
                name="deliveredAt"
                wire:model="deliveredAt"
                max="{{ now()->toDateString() }}"
                :hint="__('disbursements.delivered_at_hint')"
            />

            <div>
                <label for="notes" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">
                    {{ __('disbursements.field_notes') }}
                </label>
                <textarea
                    id="notes"
                    wire:model="notes"
                    rows="2"
                    class="block w-full rounded-(--radius-brand) border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 shadow-sm transition duration-200 ease-out focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30 dark:border-white/10 dark:bg-primary-950/30 dark:text-gray-100"
                ></textarea>
                @error('notes')
                    <p class="mt-1.5 text-xs text-status-rejected">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">
                    {{ __('disbursements.field_proof') }}
                </label>

                <label
                    for="proof-upload"
                    class="group flex cursor-pointer items-center justify-center gap-3 rounded-(--radius-brand) border-2 border-dashed border-gray-300 bg-white px-4 py-4 text-center transition duration-150 ease-out hover:border-primary-400 hover:bg-primary-50/40 dark:border-white/10 dark:bg-primary-950/20 dark:hover:border-primary-600"
                >
                    <svg class="h-6 w-6 shrink-0 text-gray-400 transition duration-150 group-hover:text-primary-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0 3 3m-3-3-3 3M6.75 19.5a4.5 4.5 0 0 1-1.41-8.775 5.25 5.25 0 0 1 10.233-2.33 3.75 3.75 0 0 1 4.132 6.331A5.25 5.25 0 0 1 17.25 19.5H6.75Z" />
                    </svg>

                    <span class="text-sm text-gray-500 dark:text-gray-400">
                        @if ($proof)
                            {{ $proof->getClientOriginalName() }}
                        @else
                            {{ __('disbursements.proof_upload_hint') }}
                        @endif
                    </span>

                    <input id="proof-upload" type="file" wire:model="proof" class="sr-only" />
                </label>

                @error('proof')
                    <p class="mt-1.5 text-xs text-status-rejected">{{ $message }}</p>
                @enderror
            </div>

            <x-ui.button
                type="button"
                variant="primary"
                class="w-full"
                wire:click="record"
                wire:confirm="{{ __('disbursements.confirm_record') }}"
            >
                {{ __('disbursements.record_button') }}
            </x-ui.button>
        </div>
    @elseif ($aid->status === \App\Enums\AidStatus::Delivered && $disbursement)
        {{-- Step 3: read-only summary and the second administrative sign-off. --}}
        <div class="space-y-5">
            <dl class="grid grid-cols-1 gap-x-6 gap-y-5 sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('disbursements.field_method') }}</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $disbursement->method->label() }}</dd>
                </div>

                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('disbursements.field_delivered_by') }}</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $disbursement->deliveredBy?->name ?: __('common.dash') }}</dd>
                </div>

                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('disbursements.field_delivered_at') }}</dt>
                    <dd class="mt-1 text-sm tabular-nums text-gray-900 dark:text-white">{{ $disbursement->delivered_at?->translatedFormat('Y/m/d H:i') ?: __('common.dash') }}</dd>
                </div>

                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __($disbursement->method->referenceLabelKey()) }}</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                        {{ $disbursement->method === \App\Enums\DisbursementMethod::BankTransfer ? ($disbursement->transfer_reference ?: __('common.dash')) : ($disbursement->receipt_number ?: __('common.dash')) }}
                    </dd>
                </div>

                @if ($disbursement->method === \App\Enums\DisbursementMethod::Courier)
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('disbursements.field_courier_name') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $disbursement->courier_name ?: __('common.dash') }}</dd>
                    </div>
                @endif

                @if ($disbursement->bank_account_masked)
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('disbursements.field_bank_account_masked') }}</dt>
                        <dd class="mt-1 font-mono text-sm tabular-nums text-gray-900 dark:text-white" dir="ltr">{{ $disbursement->bank_account_masked }}</dd>
                    </div>
                @endif

                @if ($disbursement->notes)
                    <div class="sm:col-span-2">
                        <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('disbursements.field_notes') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $disbursement->notes }}</dd>
                    </div>
                @endif
            </dl>

            <div>
                <dt class="mb-2 text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('disbursements.field_proof') }}</dt>

                @if ($media = $disbursement->getFirstMedia('delivery_proof'))
                    <div class="flex flex-wrap items-center justify-between gap-3 rounded-(--radius-brand) border border-gray-100 px-4 py-3 dark:border-white/10">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-gray-900 dark:text-white">{{ $media->file_name }}</p>
                            <p class="text-xs tabular-nums text-gray-500 dark:text-gray-400">{{ $media->human_readable_size }}</p>
                        </div>

                        <x-ui.button href="{{ route('disbursements.proof.download', $disbursement) }}" target="_blank" variant="ghost" size="sm">
                            {{ __('disbursements.proof_download') }}
                        </x-ui.button>
                    </div>
                @else
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('disbursements.no_proof') }}</p>
                @endif
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-gray-100 pt-4 dark:border-white/10">
                @if ($disbursement->confirmed_at)
                    <x-ui.badge color="approved">{{ __('disbursements.audit_confirmed_badge') }}</x-ui.badge>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ __('disbursements.audit_confirmed_by', ['name' => $disbursement->confirmedBy?->name, 'date' => $disbursement->confirmed_at?->translatedFormat('Y/m/d H:i')]) }}
                    </p>
                @else
                    <x-ui.badge color="review">{{ __('disbursements.audit_pending_badge') }}</x-ui.badge>

                    @if ($this->canConfirm)
                        <x-ui.button
                            type="button"
                            variant="secondary"
                            size="sm"
                            wire:click="confirm"
                            wire:confirm="{{ __('disbursements.confirm_confirm') }}"
                        >
                            {{ __('disbursements.confirm_button') }}
                        </x-ui.button>
                    @endif
                @endif
            </div>
        </div>
    @else
        <x-ui.empty-state :title="__('disbursements.empty_title')" :description="__('disbursements.empty_description')" />
    @endif
</x-ui.card>

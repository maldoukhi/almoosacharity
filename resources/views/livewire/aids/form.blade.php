@php
    $isEdit = $aid?->exists ?? false;

    $beneficiaryOptions = $this->beneficiaries->mapWithKeys(fn ($beneficiary) => [$beneficiary->id => $beneficiary->full_name]);
    $programOptions = $this->programs->mapWithKeys(fn ($program) => [$program->id => $program->name]);
@endphp

<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">
                {{ $isEdit ? __('aids.edit_title') : __('aids.create_title') }}
            </h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                {{ $isEdit ? __('aids.edit_subtitle') : __('aids.create_subtitle') }}
            </p>
        </div>

        <x-ui.button href="{{ route('aids.index') }}" variant="ghost">
            {{ __('common.back') }}
        </x-ui.button>
    </div>

    <x-ui.card>
        <form wire:submit="save" class="space-y-6">
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                @if ($isEdit)
                    <x-ui.select
                        :label="__('aids.field_beneficiary')"
                        name="beneficiary_id"
                        wire:model="beneficiary_id"
                        :placeholder="__('aids.select_placeholder')"
                        :options="$beneficiaryOptions"
                    />
                @else
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">
                            {{ __('aids.field_beneficiaries') }}
                        </label>

                        @if ($this->selectedBeneficiaries->isNotEmpty())
                            <div class="mb-2.5 flex flex-wrap gap-2">
                                @foreach ($this->selectedBeneficiaries as $beneficiary)
                                    <span
                                        wire:key="beneficiary-chip-{{ $beneficiary->id }}"
                                        style="animation: fade-in-up 0.2s ease-out both"
                                        class="inline-flex items-center gap-1.5 rounded-full bg-primary-50 py-1.5 ps-3 pe-2 text-xs font-medium text-primary-700 dark:bg-primary-500/20 dark:text-primary-200"
                                    >
                                        {{ $beneficiary->full_name }}
                                        <button
                                            type="button"
                                            wire:click="removeBeneficiary({{ $beneficiary->id }})"
                                            class="rounded-full p-0.5 text-primary-500 transition duration-150 ease-out hover:bg-primary-100 hover:text-primary-700 dark:text-primary-300 dark:hover:bg-primary-500/30"
                                        >
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                            </svg>
                                            <span class="sr-only">{{ __('common.delete') }}</span>
                                        </button>
                                    </span>
                                @endforeach
                            </div>
                        @endif

                        <x-ui.input
                            type="search"
                            name="beneficiarySearch"
                            wire:model.live.debounce.300ms="beneficiarySearch"
                            :placeholder="__('aids.beneficiary_search_placeholder')"
                        />

                        @if ($beneficiarySearch !== '')
                            <div class="mt-2 max-h-56 divide-y divide-gray-100 overflow-y-auto rounded-(--radius-brand) border border-gray-200 dark:divide-white/10 dark:border-white/10">
                                @forelse ($this->beneficiaries as $beneficiary)
                                    @php $alreadySelected = in_array($beneficiary->id, $beneficiary_ids, true); @endphp
                                    <button
                                        type="button"
                                        wire:key="beneficiary-option-{{ $beneficiary->id }}"
                                        wire:click="addBeneficiary({{ $beneficiary->id }})"
                                        @disabled($alreadySelected)
                                        class="flex w-full items-center justify-between px-3.5 py-2.5 text-start text-sm text-gray-700 transition duration-150 ease-out hover:bg-gray-50 disabled:cursor-not-allowed disabled:bg-gray-50 disabled:text-gray-400 dark:text-gray-200 dark:hover:bg-white/5 dark:disabled:bg-white/5 dark:disabled:text-gray-500"
                                    >
                                        <span>{{ $beneficiary->full_name }}</span>

                                        @if ($alreadySelected)
                                            <span class="text-xs text-secondary-600 dark:text-secondary-400">{{ __('aids.already_selected') }}</span>
                                        @endif
                                    </button>
                                @empty
                                    <p class="px-3.5 py-2.5 text-sm text-gray-500 dark:text-gray-400">{{ __('aids.no_beneficiaries_found') }}</p>
                                @endforelse
                            </div>
                        @endif

                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                            {{ trans_choice('aids.selected_count', count($beneficiary_ids), ['count' => count($beneficiary_ids)]) }}
                        </p>

                        @error('beneficiary_ids')
                            <p class="mt-1.5 text-xs text-status-rejected">{{ $message }}</p>
                        @enderror
                    </div>
                @endif

                <x-ui.select
                    :label="__('aids.field_program')"
                    name="aid_program_id"
                    wire:model.live="aid_program_id"
                    :placeholder="__('aids.select_placeholder')"
                    :options="$programOptions"
                />
            </div>

            <div>
                <p class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('aids.field_type') }}</p>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <label class="flex cursor-pointer items-center gap-3 rounded-(--radius-brand) border border-gray-200 px-4 py-3 text-sm transition duration-150 ease-out hover:bg-gray-50 has-checked:border-primary-400 has-checked:bg-primary-50 dark:border-white/10 dark:hover:bg-white/5 dark:has-checked:border-primary-700 dark:has-checked:bg-primary-900/30">
                        <input type="radio" wire:model.live="type" value="cash" class="h-4 w-4 border-gray-300 text-primary focus:ring-2 focus:ring-primary-500/30 dark:border-white/20" />
                        <span>
                            <span class="block font-medium text-gray-900 dark:text-white">{{ __('aids.type_cash') }}</span>
                            <span class="block text-xs text-gray-500 dark:text-gray-400">{{ __('aids.type_cash_hint') }}</span>
                        </span>
                    </label>

                    <label class="flex cursor-pointer items-center gap-3 rounded-(--radius-brand) border border-gray-200 px-4 py-3 text-sm transition duration-150 ease-out hover:bg-gray-50 has-checked:border-primary-400 has-checked:bg-primary-50 dark:border-white/10 dark:hover:bg-white/5 dark:has-checked:border-primary-700 dark:has-checked:bg-primary-900/30">
                        <input type="radio" wire:model.live="type" value="in_kind" class="h-4 w-4 border-gray-300 text-primary focus:ring-2 focus:ring-primary-500/30 dark:border-white/20" />
                        <span>
                            <span class="block font-medium text-gray-900 dark:text-white">{{ __('aids.type_in_kind') }}</span>
                            <span class="block text-xs text-gray-500 dark:text-gray-400">{{ __('aids.type_in_kind_hint') }}</span>
                        </span>
                    </label>
                </div>

                @error('type')
                    <p class="mt-1.5 text-xs text-status-rejected">{{ $message }}</p>
                @enderror
            </div>

            @if ($type === 'cash')
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 transition-opacity duration-200 ease-out">
                    <x-ui.input
                        :label="__('aids.field_amount')"
                        name="amount"
                        type="number"
                        step="0.01"
                        min="0"
                        wire:model="amount"
                        :hint="__('aids.currency_sar')"
                    />

                    <x-ui.input :label="__('aids.field_purpose')" name="purpose" wire:model="purpose" />
                </div>
            @else
                <div class="space-y-4 transition-opacity duration-200 ease-out">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('aids.field_items') }}</p>

                        <x-ui.button type="button" variant="ghost" size="sm" wire:click="addItem">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            {{ __('aids.add_item') }}
                        </x-ui.button>
                    </div>

                    @error('items')
                        <p class="text-xs text-status-rejected">{{ $message }}</p>
                    @enderror

                    <div class="space-y-3">
                        @forelse ($items as $index => $item)
                            <div
                                wire:key="aid-item-{{ $index }}"
                                style="animation: fade-in-up 0.2s ease-out both"
                                class="grid grid-cols-1 gap-3 rounded-(--radius-brand) border border-gray-200 p-4 sm:grid-cols-12 dark:border-white/10"
                            >
                                <div class="sm:col-span-4">
                                    <x-ui.input :label="__('aids.field_item_name')" name="items.{{ $index }}.name" wire:model="items.{{ $index }}.name" />
                                </div>

                                <div class="sm:col-span-2">
                                    <x-ui.input :label="__('aids.field_item_quantity')" type="number" min="1" name="items.{{ $index }}.quantity" wire:model="items.{{ $index }}.quantity" />
                                </div>

                                <div class="sm:col-span-2">
                                    <x-ui.input :label="__('aids.field_item_estimated_value')" type="number" step="0.01" min="0" name="items.{{ $index }}.estimated_value" wire:model="items.{{ $index }}.estimated_value" />
                                </div>

                                <div class="sm:col-span-3">
                                    <x-ui.input :label="__('aids.field_item_description')" name="items.{{ $index }}.description" wire:model="items.{{ $index }}.description" />
                                </div>

                                <div class="flex items-end justify-end sm:col-span-1">
                                    <x-ui.button type="button" variant="danger" size="sm" wire:click="removeItem({{ $index }})">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                        </svg>
                                        <span class="sr-only">{{ __('common.delete') }}</span>
                                    </x-ui.button>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('aids.no_items_yet') }}</p>
                        @endforelse
                    </div>
                </div>
            @endif

            <div>
                <label for="notes" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">
                    {{ __('aids.field_notes') }}
                </label>
                <textarea
                    id="notes"
                    wire:model="notes"
                    rows="3"
                    class="block w-full rounded-(--radius-brand) border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 shadow-sm transition duration-200 ease-out focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30 dark:border-white/10 dark:bg-primary-950/30 dark:text-gray-100"
                ></textarea>
                @error('notes')
                    <p class="mt-1.5 text-xs text-status-rejected">{{ $message }}</p>
                @enderror
            </div>

            @if (! $isEdit && count($beneficiary_ids) > 1)
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    {{ __('aids.bulk_create_hint', ['count' => count($beneficiary_ids)]) }}
                </p>
            @endif

            <div class="flex flex-col-reverse items-stretch justify-end gap-3 border-t border-gray-100 pt-5 sm:flex-row sm:items-center dark:border-white/10">
                <x-ui.button href="{{ route('aids.index') }}" variant="ghost">
                    {{ __('common.cancel') }}
                </x-ui.button>

                <x-ui.button type="submit" variant="ghost" wire:target="save">
                    {{ __('aids.save_draft') }}
                </x-ui.button>

                <x-ui.button
                    type="button"
                    variant="primary"
                    wire:click="saveAndSubmit"
                    wire:confirm="{{ __('aids.confirm_submit') }}"
                    wire:target="saveAndSubmit"
                >
                    {{ __('aids.save_and_submit') }}
                </x-ui.button>
            </div>
        </form>
    </x-ui.card>
</div>

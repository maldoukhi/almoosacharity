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
                    <div
                        x-data="{
                            open: false,
                            highlighted: 0,
                            get options() {
                                return Array.from(this.$refs.list?.querySelectorAll('[role=option]') ?? []);
                            },
                            openList() {
                                this.open = true;
                                this.highlighted = 0;
                                this.$nextTick(() => this.$refs.search?.focus());
                            },
                            move(delta) {
                                const max = this.options.length - 1;
                                if (max < 0) return;
                                this.highlighted = Math.min(Math.max(this.highlighted + delta, 0), max);
                                this.options[this.highlighted]?.scrollIntoView({ block: 'nearest' });
                            },
                            chooseHighlighted() {
                                this.options[this.highlighted]?.click();
                            },
                        }"
                        x-on:click.outside="open = false"
                        class="relative"
                    >
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

                        <button
                            type="button"
                            x-on:click="open ? (open = false) : openList()"
                            x-on:keydown.down.prevent="open ? move(1) : openList()"
                            x-on:keydown.up.prevent="open ? move(-1) : openList()"
                            x-on:keydown.enter.prevent="open ? chooseHighlighted() : openList()"
                            x-on:keydown.escape="open = false"
                            :aria-expanded="open.toString()"
                            aria-haspopup="listbox"
                            @class([
                                'flex w-full items-center justify-between gap-2 rounded-(--radius-brand) border bg-white ps-3.5 pe-3 py-2.5 text-start text-sm shadow-sm transition duration-200 ease-out focus:outline-none focus:ring-2 focus:ring-offset-0 dark:bg-primary-950/30',
                                'border-status-rejected focus:border-status-rejected focus:ring-status-rejected/30' => $errors->has('beneficiary_ids'),
                                'border-gray-300 focus:border-primary-500 focus:ring-primary-500/30 dark:border-white/10' => ! $errors->has('beneficiary_ids'),
                            ])
                        >
                            <span class="truncate text-gray-400 dark:text-gray-500">
                                {{ __('aids.picker_placeholder') }}
                            </span>

                            <svg
                                class="h-4 w-4 shrink-0 text-gray-400 transition-transform duration-200 ease-out"
                                :class="{ 'rotate-180': open }"
                                viewBox="0 0 20 20"
                                fill="currentColor"
                                aria-hidden="true"
                            >
                                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
                            </svg>
                        </button>

                        <div
                            x-show="open"
                            x-transition:enter="transition ease-out duration-150"
                            x-transition:enter-start="opacity-0 scale-95"
                            x-transition:enter-end="opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-100"
                            x-transition:leave-start="opacity-100 scale-100"
                            x-transition:leave-end="opacity-0 scale-95"
                            style="display: none"
                            role="listbox"
                            aria-multiselectable="true"
                            class="absolute z-30 mt-1.5 w-full rounded-(--radius-brand) bg-white p-1.5 shadow-(--shadow-card) ring-1 ring-gray-100 dark:bg-primary-950 dark:ring-white/10"
                        >
                            <input
                                type="search"
                                x-ref="search"
                                wire:model.live.debounce.300ms="beneficiarySearch"
                                x-on:keydown.down.prevent="move(1)"
                                x-on:keydown.up.prevent="move(-1)"
                                x-on:keydown.enter.prevent="chooseHighlighted()"
                                x-on:keydown.escape="open = false"
                                placeholder="{{ __('aids.beneficiary_search_placeholder') }}"
                                class="mb-1.5 block w-full rounded-(--radius-brand) border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary-500/30 dark:border-white/10 dark:bg-white/5 dark:text-gray-100"
                            />

                            <div class="mb-1.5 flex items-center justify-between gap-2 px-1">
                                <button
                                    type="button"
                                    wire:click="selectAllMatching"
                                    class="text-xs font-medium text-primary-600 transition duration-150 ease-out hover:text-primary-700 hover:underline dark:text-primary-300 dark:hover:text-primary-200"
                                >
                                    {{ __('aids.select_all') }}
                                </button>

                                <button
                                    type="button"
                                    wire:click="clearSelection"
                                    class="text-xs font-medium text-gray-500 transition duration-150 ease-out hover:text-gray-700 hover:underline dark:text-gray-400 dark:hover:text-gray-200"
                                >
                                    {{ __('aids.clear_selection') }}
                                </button>
                            </div>

                            <ul x-ref="list" class="max-h-56 space-y-0.5 overflow-y-auto" wire:loading.class="opacity-50" wire:target="beneficiarySearch">
                                @forelse ($this->beneficiaries as $index => $beneficiary)
                                    @php $alreadySelected = in_array($beneficiary->id, $beneficiary_ids, true); @endphp
                                    <li
                                        wire:key="beneficiary-option-{{ $beneficiary->id }}"
                                        wire:click="{{ $alreadySelected ? 'removeBeneficiary' : 'addBeneficiary' }}({{ $beneficiary->id }})"
                                        x-on:mouseenter="highlighted = {{ $index }}"
                                        :class="{ 'bg-primary-50 dark:bg-primary-900/30': highlighted === {{ $index }} }"
                                        class="flex cursor-pointer items-center justify-between gap-2 rounded-(--radius-brand) px-3 py-2 text-sm text-gray-700 dark:text-gray-200"
                                        role="option"
                                        aria-selected="{{ $alreadySelected ? 'true' : 'false' }}"
                                    >
                                        <span @class(['truncate', 'font-medium text-primary-700 dark:text-primary-200' => $alreadySelected])>
                                            {{ $beneficiary->full_name }}
                                        </span>

                                        @if ($alreadySelected)
                                            <svg class="h-4 w-4 shrink-0 text-secondary-600 dark:text-secondary-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" />
                                            </svg>
                                        @endif
                                    </li>
                                @empty
                                    <p class="px-3 py-2 text-sm text-gray-400 dark:text-gray-500">{{ __('aids.no_results') }}</p>
                                @endforelse
                            </ul>
                        </div>

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

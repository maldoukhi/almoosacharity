@php
    $isEdit = $aid?->exists ?? false;

    $beneficiaryOptions = $this->beneficiaries->mapWithKeys(fn ($beneficiary) => [$beneficiary->id => $beneficiary->full_name]);
    $programOptions = $this->programs->mapWithKeys(fn ($program) => [$program->id => $program->name]);
    $frequencyOptions = collect(\App\Enums\RecurrenceFrequency::cases())->mapWithKeys(fn ($frequency) => [$frequency->value => $frequency->label()]);
    $existingDocuments = $isEdit ? $aid->getMedia('aid_documents') : collect();
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
            <x-ui.input
                :label="__('aids.field_title')"
                name="title"
                wire:model="title"
                :hint="__('aids.field_title_hint')"
                :placeholder="__('aids.field_title_placeholder')"
            />

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

            {{-- Supporting documents (phase 9) --}}
            <div class="space-y-3 border-t border-gray-100 pt-5 dark:border-white/10">
                <div>
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('aids.documents.title') }}</p>
                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ __('aids.documents.hint') }}</p>
                </div>

                @if ($existingDocuments->isNotEmpty())
                    <ul class="space-y-1.5">
                        @foreach ($existingDocuments as $media)
                            <li wire:key="aid-document-{{ $media->id }}" class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                                <svg class="h-4 w-4 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                </svg>
                                <span class="truncate">{{ $media->name }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif

                <input
                    type="file"
                    wire:model="documents"
                    multiple
                    accept=".pdf,.jpg,.jpeg,.png"
                    class="block w-full text-sm text-gray-600 file:me-3 file:rounded-(--radius-brand) file:border-0 file:bg-primary-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-primary-700 hover:file:bg-primary-100 dark:text-gray-300 dark:file:bg-primary-500/20 dark:file:text-primary-200"
                />

                <div wire:loading wire:target="documents" class="text-xs text-gray-500 dark:text-gray-400">{{ __('aids.documents.uploading') }}</div>

                @error('documents.*')
                    <p class="text-xs text-status-rejected">{{ $message }}</p>
                @enderror

                @if (! empty($documents))
                    <p class="text-xs text-secondary-700 dark:text-secondary-400">
                        {{ trans_choice('aids.documents.pending_count', count($documents), ['count' => count($documents)]) }}
                    </p>
                @endif
            </div>

            {{-- Recurrence (phase 10) — a prominent, standalone card so the
                 recurring-aid schedule is easy to find, not buried in a toggle. --}}
            <div class="overflow-hidden rounded-(--radius-brand) border border-primary-100 bg-primary-50/40 dark:border-primary-500/20 dark:bg-primary-500/5">
                <div class="flex items-start gap-3 border-b border-primary-100/70 px-5 py-4 dark:border-primary-500/20">
                    <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-primary-100 text-primary-700 dark:bg-primary-500/20 dark:text-primary-200">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                        </svg>
                    </span>
                    <div>
                        <h2 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('aids.recurrence.card_title') }}</h2>
                        <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">{{ __('aids.recurrence.card_subtitle') }}</p>
                    </div>
                </div>

                <div class="space-y-4 px-5 py-4">
                    <x-ui.toggle
                        :label="__('aids.recurrence.enable')"
                        :description="__('aids.recurrence.enable_hint')"
                        name="isRecurring"
                        wire:model.live="isRecurring"
                    />

                    @if ($isRecurring)
                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                            <x-ui.select
                                :label="__('aids.recurrence.frequency_label')"
                                name="recurrenceFrequency"
                                wire:model.live="recurrenceFrequency"
                                :options="$frequencyOptions"
                            />

                            @if ($recurrenceFrequency === \App\Enums\RecurrenceFrequency::CustomMonths->value)
                                <x-ui.input
                                    :label="__('aids.recurrence.interval_months')"
                                    name="recurrenceIntervalMonths"
                                    type="number"
                                    min="1"
                                    max="60"
                                    wire:model="recurrenceIntervalMonths"
                                />
                            @endif

                            <x-ui.input
                                :label="__('aids.recurrence.starts_on')"
                                name="recurrenceStartsOn"
                                type="date"
                                wire:model="recurrenceStartsOn"
                                :hint="__('aids.recurrence.starts_on_hint')"
                            />

                            <x-ui.input
                                :label="__('aids.recurrence.due_on')"
                                name="recurrenceDueOn"
                                type="date"
                                wire:model="recurrenceDueOn"
                                :hint="__('aids.recurrence.due_on_hint')"
                            />

                            <x-ui.input
                                :label="__('aids.recurrence.title_template')"
                                name="recurrenceTitleTemplate"
                                wire:model="recurrenceTitleTemplate"
                                :hint="__('aids.recurrence.title_template_hint')"
                            />

                            <x-ui.input
                                :label="__('aids.recurrence.ends_on')"
                                name="recurrenceEndsOn"
                                type="date"
                                wire:model="recurrenceEndsOn"
                                :hint="__('aids.recurrence.ends_on_hint')"
                            />

                            <x-ui.input
                                :label="__('aids.recurrence.lead_days')"
                                name="recurrenceLeadDays"
                                type="number"
                                min="0"
                                max="365"
                                wire:model="recurrenceLeadDays"
                                :hint="__('aids.recurrence.lead_days_hint')"
                            />
                        </div>

                        <x-ui.toggle
                            :label="__('aids.recurrence.active')"
                            :description="__('aids.recurrence.active_hint')"
                            name="recurrenceActive"
                            wire:model="recurrenceActive"
                        />
                    @endif
                </div>
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
                    wire:click="confirmSubmit"
                    wire:target="confirmSubmit"
                >
                    {{ __('aids.save_and_submit') }}
                </x-ui.button>
            </div>
        </form>
    </x-ui.card>

    {{-- Confirm before saving & submitting for approval --}}
    @if ($showSubmitConfirm)
        @php
            $confirmCount = $isEdit ? 1 : count($beneficiary_ids);
        @endphp
        <div class="fixed inset-0 z-[70] overflow-y-auto" role="dialog" aria-modal="true">
            <div class="absolute inset-0 bg-primary-950/60 backdrop-blur-sm" wire:click="cancelSubmit"></div>

            <div class="flex min-h-dvh items-center justify-center p-4">
                <div class="relative w-full max-w-lg overflow-hidden rounded-(--radius-brand) bg-white shadow-xl dark:bg-primary-950 dark:ring-1 dark:ring-white/10">
                    <div class="flex items-start gap-3 border-b border-gray-100 px-6 py-4 dark:border-white/10">
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-primary-50 text-primary-600 dark:bg-primary-500/15 dark:text-primary-300">
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                        </span>
                        <div>
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('aids.submit_confirm.title') }}</h3>
                            <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">{{ __('aids.submit_confirm.subtitle') }}</p>
                        </div>
                    </div>

                    <div class="px-6 py-5">
                        <dl class="divide-y divide-gray-100 rounded-(--radius-brand) border border-gray-100 dark:divide-white/10 dark:border-white/10">
                            <div class="flex items-center justify-between gap-3 px-4 py-2.5">
                                <dt class="text-sm text-gray-500 dark:text-gray-400">{{ __('aids.field_title') }}</dt>
                                <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ filled($title) ? $title : __('aids.submit_confirm.none') }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-3 px-4 py-2.5">
                                <dt class="text-sm text-gray-500 dark:text-gray-400">{{ __('aids.field_program') }}</dt>
                                <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ $programOptions[$aid_program_id] ?? __('aids.submit_confirm.none') }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-3 px-4 py-2.5">
                                <dt class="text-sm text-gray-500 dark:text-gray-400">{{ __('aids.field_type') }}</dt>
                                <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ \App\Enums\AidType::from($type)->label() }}</dd>
                            </div>
                            @if ($type === \App\Enums\AidType::Cash->value)
                                <div class="flex items-center justify-between gap-3 px-4 py-2.5">
                                    <dt class="text-sm text-gray-500 dark:text-gray-400">{{ __('aids.field_amount') }}</dt>
                                    <dd class="text-sm font-semibold tabular-nums text-gray-900 dark:text-white">
                                        {{ __('aids.currency_sar') }} {{ number_format((float) $amount, 2) }}
                                    </dd>
                                </div>
                                <div class="flex items-center justify-between gap-3 px-4 py-2.5">
                                    <dt class="text-sm text-gray-500 dark:text-gray-400">{{ __('aids.field_purpose') }}</dt>
                                    <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ filled($purpose) ? $purpose : __('aids.submit_confirm.none') }}</dd>
                                </div>
                            @else
                                <div class="flex items-center justify-between gap-3 px-4 py-2.5">
                                    <dt class="text-sm text-gray-500 dark:text-gray-400">{{ __('aids.field_items') }}</dt>
                                    <dd class="text-sm font-semibold tabular-nums text-gray-900 dark:text-white">
                                        {{ trans_choice('aids.submit_confirm.items_count', count($items), ['count' => count($items)]) }}
                                    </dd>
                                </div>
                            @endif
                            <div class="flex items-center justify-between gap-3 px-4 py-2.5">
                                <dt class="text-sm text-gray-500 dark:text-gray-400">{{ __('aids.field_beneficiaries') }}</dt>
                                <dd class="text-sm font-semibold tabular-nums text-gray-900 dark:text-white">{{ $confirmCount }}</dd>
                            </div>
                        </dl>

                        @if ($isRecurring)
                            <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                                {{ __('aids.submit_confirm.recurring_note', [
                                    'frequency' => \App\Enums\RecurrenceFrequency::from($recurrenceFrequency)->label(),
                                    'date' => filled($recurrenceDueOn) ? $recurrenceDueOn : $recurrenceStartsOn,
                                ]) }}
                            </p>
                        @endif

                        @if (! $isEdit && $confirmCount > 1)
                            <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">{{ __('aids.submit_confirm.bulk_note', ['count' => $confirmCount]) }}</p>
                        @endif
                    </div>

                    <div class="flex items-center justify-end gap-3 border-t border-gray-100 px-6 py-4 dark:border-white/10">
                        <x-ui.button type="button" variant="ghost" wire:click="cancelSubmit">
                            {{ __('common.cancel') }}
                        </x-ui.button>
                        <x-ui.button type="button" variant="primary" wire:click="saveAndSubmit" wire:target="saveAndSubmit" wire:loading.attr="disabled">
                            {{ __('aids.save_and_submit') }}
                        </x-ui.button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

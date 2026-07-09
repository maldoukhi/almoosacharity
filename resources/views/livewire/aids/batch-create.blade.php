@php
    $programOptions = $this->programs->mapWithKeys(fn ($program) => [$program->id => $program->name]);
    $modeOptions = [
        \App\Enums\AidType::Cash->value => __('aid_batches.mode_cash'),
        \App\Enums\AidType::InKind->value => __('aid_batches.mode_in_kind'),
        'both' => __('aid_batches.mode_both'),
    ];
@endphp

<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ __('aid_batches.title') }}</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('aid_batches.subtitle') }}</p>
        </div>

        <x-ui.button href="{{ route('aids.index') }}" variant="ghost">
            {{ __('common.back') }}
        </x-ui.button>
    </div>

    @if ($summary)
        <x-ui.card>
            <div class="flex items-start gap-3">
                <div class="rounded-full bg-status-approved/10 p-2 text-status-approved">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('aid_batches.summary_title') }}</h2>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                        {{ __('aid_batches.summary_body', ['cash' => $summary['cash'], 'in_kind' => $summary['in_kind'], 'beneficiaries' => $summary['beneficiaries']]) }}
                    </p>
                </div>
            </div>
        </x-ui.card>
    @endif

    {{-- Step 1: pick categories / program --}}
    <x-ui.card>
        <h2 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">{{ __('aid_batches.step_select') }}</h2>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <div>
                <span class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('aid_batches.categories') }}</span>
                <div class="flex flex-wrap gap-2">
                    @forelse ($this->categories as $category)
                        <label wire:key="cat-{{ $category->id }}" class="inline-flex cursor-pointer items-center gap-2 rounded-(--radius-brand) border border-gray-300 px-3 py-2 text-sm text-gray-700 transition hover:bg-gray-50 dark:border-white/10 dark:text-gray-200 dark:hover:bg-white/5">
                            <input
                                type="checkbox"
                                value="{{ $category->id }}"
                                wire:model.live="category_ids"
                                class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500 dark:border-white/20 dark:bg-primary-950/40"
                            />
                            {{ $category->name }}
                        </label>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('aid_batches.no_categories') }}</p>
                    @endforelse
                </div>
                @error('selected_ids')
                    <p class="mt-2 text-xs text-status-rejected">{{ $message }}</p>
                @enderror
            </div>

            <x-ui.select
                :label="__('aid_batches.program')"
                name="aid_program_id"
                wire:model.live="aid_program_id"
                :placeholder="__('aids.select_placeholder')"
                :options="$programOptions"
                :hint="__('aid_batches.program_hint')"
            />
        </div>

        {{-- Excel national-id upload --}}
        <div class="mt-6 border-t border-gray-100 pt-6 dark:border-white/10">
            <span class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('aid_batches.excel_upload') }}</span>
            <p class="mb-2 text-xs text-gray-500 dark:text-gray-400">{{ __('aid_batches.excel_hint') }}</p>
            <input
                type="file"
                wire:model="nationalIdFile"
                accept=".xlsx,.xls,.csv"
                class="block w-full text-sm text-gray-700 file:me-4 file:rounded-(--radius-brand) file:border-0 file:bg-primary-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-primary-700 hover:file:bg-primary-100 dark:text-gray-200 dark:file:bg-primary-900/40 dark:file:text-primary-200"
            />
            <div wire:loading wire:target="nationalIdFile" class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ __('aid_batches.excel_reading') }}</div>
            @error('nationalIdFile')
                <p class="mt-1.5 text-xs text-status-rejected">{{ $message }}</p>
            @enderror

            @if ($unmatchedNationalIds !== [])
                <div class="mt-3 rounded-(--radius-brand) border border-status-review/30 bg-status-review/5 p-3">
                    <p class="text-xs font-medium text-status-review">{{ __('aid_batches.unmatched_title', ['count' => count($unmatchedNationalIds)]) }}</p>
                    <p class="mt-1 font-mono text-xs text-gray-600 dark:text-gray-300">{{ implode('، ', $unmatchedNationalIds) }}</p>
                </div>
            @endif
        </div>
    </x-ui.card>

    {{-- Step 2: aid details --}}
    <x-ui.card>
        <h2 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">{{ __('aid_batches.step_details') }}</h2>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <x-ui.select
                :label="__('aid_batches.mode')"
                name="mode"
                wire:model.live="mode"
                :options="$modeOptions"
            />

            <x-ui.input
                :label="__('aid_batches.note')"
                name="note"
                wire:model="note"
                :placeholder="__('aid_batches.note_placeholder')"
            />
        </div>

        @if ($this->isCash)
            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-ui.input
                    :label="__('aid_batches.default_amount')"
                    name="default_amount"
                    type="number"
                    step="0.01"
                    min="0.01"
                    wire:model="default_amount"
                    :hint="__('aid_batches.default_amount_hint')"
                />
                <x-ui.input
                    :label="__('aid_batches.default_purpose')"
                    name="default_purpose"
                    wire:model="default_purpose"
                />
            </div>
        @endif

        @if ($this->isInKind)
            <div class="mt-4">
                <div class="mb-2 flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('aid_batches.default_items') }}</span>
                    <x-ui.button variant="ghost" size="sm" wire:click="addItem">{{ __('aid_batches.add_item') }}</x-ui.button>
                </div>

                @error('default_items')
                    <p class="mb-2 text-xs text-status-rejected">{{ $message }}</p>
                @enderror

                <div class="space-y-2">
                    @foreach ($default_items as $index => $item)
                        <div wire:key="item-{{ $index }}" class="grid grid-cols-1 gap-2 rounded-(--radius-brand) border border-gray-200 p-3 sm:grid-cols-12 dark:border-white/10">
                            <div class="sm:col-span-6">
                                <x-ui.input
                                    :label="__('aids.field_item_name')"
                                    :name="'default_items.'.$index.'.name'"
                                    wire:model="default_items.{{ $index }}.name"
                                />
                            </div>
                            <div class="sm:col-span-3">
                                <x-ui.input
                                    :label="__('aids.field_item_quantity')"
                                    :name="'default_items.'.$index.'.quantity'"
                                    type="number"
                                    min="1"
                                    wire:model="default_items.{{ $index }}.quantity"
                                />
                            </div>
                            <div class="sm:col-span-3 flex items-end">
                                <x-ui.button variant="danger" size="sm" wire:click="removeItem({{ $index }})" class="w-full">
                                    {{ __('common.delete') }}
                                </x-ui.button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </x-ui.card>

    {{-- Step 3: beneficiary selection --}}
    <x-ui.card>
        <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                {{ __('aid_batches.step_beneficiaries') }}
                <span class="ms-1 text-sm font-normal text-gray-500 dark:text-gray-400">({{ count($selected_ids) }})</span>
            </h2>
            <div class="flex items-center gap-2">
                <x-ui.button variant="ghost" size="sm" wire:click="selectAll">{{ __('aids.select_all') }}</x-ui.button>
                <x-ui.button variant="ghost" size="sm" wire:click="clearSelection">{{ __('aids.clear_selection') }}</x-ui.button>
            </div>
        </div>

        <div class="mb-4">
            <x-ui.input
                name="search"
                wire:model.live.debounce.300ms="search"
                :placeholder="__('aid_batches.search_placeholder')"
            />
        </div>

        @if ($this->beneficiaries->isEmpty())
            <x-ui.empty-state :title="__('aid_batches.empty_title')" :description="__('aid_batches.empty_description')">
                <x-slot:icon>
                    <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                    </svg>
                </x-slot:icon>
            </x-ui.empty-state>
        @else
            <x-ui.table>
                <thead>
                    <tr>
                        <x-ui.table.th></x-ui.table.th>
                        <x-ui.table.th>{{ __('aids.field_beneficiary') }}</x-ui.table.th>
                        <x-ui.table.th>{{ __('beneficiaries.field_national_id') }}</x-ui.table.th>
                        @if ($this->isCash)
                            <x-ui.table.th align="end">{{ __('aid_batches.amount_override') }}</x-ui.table.th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                    @foreach ($this->beneficiaries as $beneficiary)
                        <tr wire:key="ben-{{ $beneficiary->id }}" class="transition duration-150 hover:bg-gray-50 dark:hover:bg-white/5">
                            <x-ui.table.td>
                                <input
                                    type="checkbox"
                                    value="{{ $beneficiary->id }}"
                                    wire:model.live="selected_ids"
                                    class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500 dark:border-white/20 dark:bg-primary-950/40"
                                />
                            </x-ui.table.td>
                            <x-ui.table.td class="text-gray-900 dark:text-white">{{ $beneficiary->full_name }}</x-ui.table.td>
                            <x-ui.table.td class="font-mono tabular-nums text-gray-500 dark:text-gray-400">{{ $beneficiary->national_id }}</x-ui.table.td>
                            @if ($this->isCash)
                                <x-ui.table.td align="end">
                                    @if (in_array($beneficiary->id, $selected_ids, true))
                                        <input
                                            type="number"
                                            step="0.01"
                                            min="0.01"
                                            wire:model="overrideAmounts.{{ $beneficiary->id }}"
                                            placeholder="{{ $default_amount ? number_format((float) $default_amount, 2) : __('aid_batches.default') }}"
                                            class="w-32 rounded-(--radius-brand) border border-gray-300 bg-white px-2 py-1 text-sm text-gray-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30 dark:border-white/10 dark:bg-primary-950/30 dark:text-gray-100"
                                        />
                                    @else
                                        <span class="text-gray-300 dark:text-gray-600">{{ __('common.dash') }}</span>
                                    @endif
                                </x-ui.table.td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </x-ui.table>
        @endif
    </x-ui.card>

    <div class="flex items-center justify-end gap-3">
        <x-ui.button href="{{ route('aids.index') }}" variant="ghost">{{ __('common.cancel') }}</x-ui.button>
        <x-ui.button variant="primary" wire:click="create">{{ __('aid_batches.submit') }}</x-ui.button>
    </div>
</div>

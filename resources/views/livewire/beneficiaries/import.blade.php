<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ __('beneficiaries.import.title') }}</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('beneficiaries.import.subtitle') }}</p>
        </div>

        <x-ui.button href="{{ route('admin.beneficiaries.index') }}" variant="ghost">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 15.75 3 12m0 0 3.75-3.75M3 12h18" />
            </svg>
            {{ __('beneficiaries.import.back_to_list') }}
        </x-ui.button>
    </div>

    {{-- Step 1: upload --}}
    @if ($step === 'upload')
        <x-ui.card>
            <div class="space-y-4">
                <div>
                    <h2 class="text-lg font-medium text-gray-900 dark:text-white">{{ __('beneficiaries.import.upload_title') }}</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('beneficiaries.import.upload_hint') }}</p>
                </div>

                <label
                    for="import-file"
                    class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-(--radius-brand) border-2 border-dashed border-gray-300 bg-gray-50 px-6 py-10 text-center transition hover:border-primary-400 dark:border-white/15 dark:bg-primary-950/30"
                >
                    <svg class="h-8 w-8 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
                    </svg>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('beneficiaries.import.choose_file') }}</span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ __('beneficiaries.import.accepted_formats') }}</span>
                    <input id="import-file" type="file" wire:model="file" accept=".xlsx,.xls,.csv" class="sr-only" />
                </label>

                <div wire:loading wire:target="file" class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                    <x-ui.skeleton height="1.5rem" />
                </div>

                @error('file')
                    <p class="text-sm text-status-rejected">{{ $message }}</p>
                @enderror
            </div>
        </x-ui.card>
    @endif

    {{-- Step 2: map columns --}}
    @if ($step === 'map')
        <x-ui.card>
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-medium text-gray-900 dark:text-white">{{ __('beneficiaries.import.preview_title') }}</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('beneficiaries.import.preview_hint') }}</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <x-ui.table>
                    <thead>
                        <tr>
                            @foreach ($headers as $label)
                                <x-ui.table.th wire:key="head-{{ $loop->index }}">{{ $label }}</x-ui.table.th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                        @foreach ($previewRows as $row)
                            <tr wire:key="preview-{{ $loop->index }}">
                                @foreach ($headers as $colIndex => $label)
                                    <x-ui.table.td wire:key="cell-{{ $loop->parent->index }}-{{ $colIndex }}">
                                        {{ $row[$colIndex] ?? '' }}
                                    </x-ui.table.td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </x-ui.table>
            </div>
        </x-ui.card>

        <x-ui.card>
            <div class="mb-4">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white">{{ __('beneficiaries.import.mapping_title') }}</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('beneficiaries.import.mapping_hint') }}</p>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($this->fields() as $field)
                    <div wire:key="map-{{ $field }}">
                        <x-ui.select
                            :name="'mapping.'.$field"
                            wire:model="mapping.{{ $field }}"
                            :placeholder="__('beneficiaries.import.skip_column')"
                            :options="$this->columnOptions()"
                        >
                            <x-slot:label>
                                {{ __('beneficiaries.field_'.$field) }}
                                @if (in_array($field, $this->requiredFields(), true))
                                    <span class="text-status-rejected">*</span>
                                @endif
                            </x-slot:label>
                        </x-ui.select>
                    </div>
                @endforeach
            </div>

            <div class="mt-6 flex flex-wrap items-center justify-end gap-2">
                <x-ui.button variant="ghost" wire:click="startOver">
                    {{ __('beneficiaries.import.start_over') }}
                </x-ui.button>
                <x-ui.button variant="primary" wire:click="import" wire:target="import" wire:loading.attr="disabled">
                    <svg wire:loading.remove wire:target="import" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                    </svg>
                    {{ __('beneficiaries.import.run_button') }}
                </x-ui.button>
            </div>
        </x-ui.card>
    @endif

    {{-- Step 3: results --}}
    @if ($step === 'done' && $summary !== null)
        <x-ui.card>
            <div class="space-y-6">
                <div>
                    <h2 class="text-lg font-medium text-gray-900 dark:text-white">{{ __('beneficiaries.import.result_title') }}</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('beneficiaries.import.result_hint') }}</p>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <x-ui.stat-card
                        :label="__('beneficiaries.import.stat_created')"
                        :value="$summary['created']"
                    />
                    <x-ui.stat-card
                        :label="__('beneficiaries.import.stat_duplicates')"
                        :value="$summary['duplicates']"
                    />
                    <x-ui.stat-card
                        :label="__('beneficiaries.import.stat_errors')"
                        :value="count($summary['errors'])"
                    />
                </div>

                @if (count($summary['errors']) > 0)
                    <div>
                        <h3 class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('beneficiaries.import.errors_title') }}</h3>
                        <div class="overflow-x-auto">
                            <x-ui.table>
                                <thead>
                                    <tr>
                                        <x-ui.table.th>{{ __('beneficiaries.import.error_row') }}</x-ui.table.th>
                                        <x-ui.table.th>{{ __('beneficiaries.import.error_reason') }}</x-ui.table.th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                                    @foreach ($summary['errors'] as $error)
                                        <tr wire:key="error-{{ $loop->index }}">
                                            <x-ui.table.td class="tabular-nums">{{ $error['row'] }}</x-ui.table.td>
                                            <x-ui.table.td>{{ $error['reason'] }}</x-ui.table.td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </x-ui.table>
                        </div>
                    </div>
                @endif

                <div class="flex flex-wrap items-center justify-end gap-2">
                    <x-ui.button variant="ghost" wire:click="startOver">
                        {{ __('beneficiaries.import.import_another') }}
                    </x-ui.button>
                    <x-ui.button href="{{ route('admin.beneficiaries.index') }}" variant="primary">
                        {{ __('beneficiaries.import.back_to_list') }}
                    </x-ui.button>
                </div>
            </div>
        </x-ui.card>
    @endif
</div>

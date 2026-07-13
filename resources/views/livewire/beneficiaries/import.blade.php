<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ __('beneficiaries.import.title') }}</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('beneficiaries.import.subtitle') }}</p>
        </div>

        <div class="flex items-center gap-2">
            <x-ui.help-link section="import" />

            <x-ui.button href="{{ route('admin.beneficiaries.index') }}" variant="ghost">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 15.75 3 12m0 0 3.75-3.75M3 12h18" />
                </svg>
                {{ __('beneficiaries.import.back_to_list') }}
            </x-ui.button>
        </div>
    </div>

    {{-- Step 1: upload --}}
    @if ($step === 'upload')
        <x-ui.card>
            <div class="space-y-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h2 class="text-lg font-medium text-gray-900 dark:text-white">{{ __('beneficiaries.import.upload_title') }}</h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('beneficiaries.import.upload_hint') }}</p>
                    </div>

                    <x-ui.button wire:click="downloadTemplate" variant="secondary" size="sm" class="shrink-0">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                        </svg>
                        {{ __('beneficiaries.import.template_button') }}
                    </x-ui.button>
                </div>

                <div class="rounded-(--radius-brand) bg-primary-50/60 px-4 py-3 text-xs leading-relaxed text-primary-800 dark:bg-primary-950/40 dark:text-primary-100">
                    {{ __('beneficiaries.import.template_hint') }}
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

    {{-- Step 2: review + import --}}
    @if ($step === 'review')
        @php
            $statusStyles = [
                'valid' => ['color' => 'approved', 'label' => __('beneficiaries.import.status_valid')],
                'error' => ['color' => 'rejected', 'label' => __('beneficiaries.import.status_error')],
                'duplicate_system' => ['color' => 'review', 'label' => __('beneficiaries.import.status_duplicate_system')],
                'duplicate_file' => ['color' => 'gray', 'label' => __('beneficiaries.import.status_duplicate_file')],
                'excluded' => ['color' => 'gray', 'label' => __('beneficiaries.import.status_excluded')],
            ];
            $columnCount = count($headers);
            $spanAll = $columnCount + 2;
        @endphp

        <x-ui.card>
            <div class="space-y-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-medium text-gray-900 dark:text-white">{{ __('beneficiaries.import.review_title') }}</h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('beneficiaries.import.review_hint') }}</p>
                    </div>

                    {{-- Live tally chips --}}
                    <div class="flex flex-wrap items-center gap-2">
                        <x-ui.badge color="gray">
                            {{ __('beneficiaries.import.stat_total') }}: <span class="font-semibold">{{ $stats['total'] }}</span>
                        </x-ui.badge>
                        @if ($ready)
                            <x-ui.badge color="approved">{{ __('beneficiaries.import.status_valid') }}: <span class="font-semibold">{{ $stats['valid'] }}</span></x-ui.badge>
                            @if ($stats['error'] > 0)
                                <x-ui.badge color="rejected">{{ __('beneficiaries.import.status_error') }}: <span class="font-semibold">{{ $stats['error'] }}</span></x-ui.badge>
                            @endif
                            @if ($stats['duplicate_system'] > 0)
                                <x-ui.badge color="review">{{ __('beneficiaries.import.status_duplicate_system') }}: <span class="font-semibold">{{ $stats['duplicate_system'] }}</span></x-ui.badge>
                            @endif
                            @if ($stats['duplicate_file'] > 0)
                                <x-ui.badge color="gray">{{ __('beneficiaries.import.status_duplicate_file') }}: <span class="font-semibold">{{ $stats['duplicate_file'] }}</span></x-ui.badge>
                            @endif
                            @if ($stats['excluded'] > 0)
                                <x-ui.badge color="gray">{{ __('beneficiaries.import.status_excluded') }}: <span class="font-semibold">{{ $stats['excluded'] }}</span></x-ui.badge>
                            @endif
                        @endif
                    </div>
                </div>

                @if ($officialTemplateDetected)
                    <div class="flex items-center gap-2 rounded-(--radius-brand) border border-status-approved/20 bg-status-approved/5 px-4 py-3 text-sm text-status-approved">
                        <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                        <span>{{ __('beneficiaries.import.auto_detected_hint') }}</span>
                    </div>
                @endif

                {{-- Hints --}}
                <div class="rounded-(--radius-brand) bg-primary-50/60 px-4 py-3 text-xs leading-relaxed text-primary-800 dark:bg-primary-950/40 dark:text-primary-100">
                    <p>{{ __('beneficiaries.import.map_columns_hint') }}</p>
                    <p class="mt-1">{{ __('beneficiaries.import.edit_hint') }}</p>
                </div>

                @unless ($ready)
                    <div class="flex items-start gap-2 rounded-(--radius-brand) border border-status-review/30 bg-status-review/5 px-4 py-3 text-sm text-status-review">
                        <svg class="mt-0.5 h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                        </svg>
                        <span>{{ __('beneficiaries.import.required_notice') }}</span>
                    </div>
                @endunless

                {{-- Toolbar: grouping + bulk include/exclude --}}
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <div class="w-full sm:w-64">
                        <label for="group-by" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">
                            {{ __('beneficiaries.import.group_by_label') }}
                        </label>
                        <div class="relative">
                            <select
                                id="group-by"
                                wire:model.live="groupBy"
                                class="block w-full appearance-none rounded-(--radius-brand) border border-gray-300 bg-white ps-3.5 pe-9 py-2.5 text-sm text-gray-900 shadow-sm transition focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30 dark:border-white/10 dark:bg-primary-950/30 dark:text-gray-100"
                            >
                                <option value="">{{ __('beneficiaries.import.group_by_none') }}</option>
                                @foreach ($headers as $col => $label)
                                    <option wire:key="group-opt-{{ $col }}" value="{{ $col }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            <svg class="pointer-events-none absolute end-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400 dark:text-gray-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
                            </svg>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <x-ui.button variant="ghost" size="sm" wire:click="setAllIncluded(true)">{{ __('beneficiaries.import.include_all') }}</x-ui.button>
                        <x-ui.button variant="ghost" size="sm" wire:click="setAllIncluded(false)">{{ __('beneficiaries.import.exclude_all') }}</x-ui.button>
                    </div>
                </div>

                {{-- The smart table: header pickers + editable, flaggable rows --}}
                <div class="overflow-x-auto rounded-(--radius-brand) border border-gray-100 dark:border-white/10">
                    <table class="w-full min-w-full divide-y divide-gray-100 text-start text-sm tabular-nums dark:divide-white/10">
                        <thead>
                            <tr>
                                <th class="sticky start-0 z-10 w-16 bg-gray-50 px-3 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-500 dark:bg-white/5 dark:text-gray-400">
                                    {{ __('beneficiaries.import.col_include') }}
                                </th>
                                <th class="min-w-40 bg-gray-50 px-3 py-3 text-start text-xs font-semibold uppercase tracking-wide text-gray-500 dark:bg-white/5 dark:text-gray-400">
                                    {{ __('beneficiaries.import.col_status') }}
                                </th>
                                @foreach ($headers as $col => $label)
                                    <th wire:key="head-{{ $col }}" class="min-w-48 bg-gray-50 px-3 py-2 align-top dark:bg-white/5">
                                        <div class="space-y-1.5">
                                            <p class="truncate text-xs font-semibold text-gray-600 dark:text-gray-300" title="{{ $label }}">{{ $label }}</p>
                                            <div class="relative">
                                                <select
                                                    wire:model.live="columnMapping.{{ $col }}"
                                                    aria-label="{{ $label }}"
                                                    @class([
                                                        'block w-full appearance-none rounded-(--radius-brand) border ps-2.5 pe-8 py-1.5 text-xs font-medium shadow-sm transition focus:outline-none focus:ring-2 focus:ring-primary-500/30',
                                                        'border-primary-300 bg-primary-50 text-primary-800 dark:border-primary-500/40 dark:bg-primary-500/15 dark:text-primary-100' => ($columnMapping[$col] ?? '') !== '',
                                                        'border-gray-300 bg-white text-gray-500 dark:border-white/10 dark:bg-primary-950/30 dark:text-gray-400' => ($columnMapping[$col] ?? '') === '',
                                                    ])
                                                >
                                                    <option value="">{{ __('beneficiaries.import.skip_column') }}</option>
                                                    @foreach ($this->fieldOptions() as $field => $fieldLabel)
                                                        <option wire:key="opt-{{ $col }}-{{ $field }}" value="{{ $field }}">{{ $fieldLabel }}</option>
                                                    @endforeach
                                                </select>
                                                <svg class="pointer-events-none absolute end-2 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-current opacity-60" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
                                                </svg>
                                            </div>
                                        </div>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                            @if ($stats['total'] === 0)
                                <tr>
                                    <td colspan="{{ $spanAll }}" class="px-4 py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                                        {{ __('beneficiaries.import.no_rows') }}
                                    </td>
                                </tr>
                            @endif

                            @foreach ($groups as $groupValue => $indexes)
                                @if ($grouped)
                                    <tr wire:key="group-{{ md5($groupValue) }}" class="bg-gray-50/80 dark:bg-white/5">
                                        <td colspan="{{ $spanAll }}" class="px-3 py-2">
                                            <div class="flex flex-wrap items-center justify-between gap-2">
                                                <div class="flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-200">
                                                    <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z" />
                                                    </svg>
                                                    <span>{{ $groupValue }}</span>
                                                    <span class="text-xs font-normal text-gray-400">{{ trans_choice('beneficiaries.import.group_count', count($indexes), ['count' => count($indexes)]) }}</span>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <button type="button" wire:click="setGroupIncluded(@js($groupValue), true)" class="text-xs font-medium text-primary-700 hover:underline dark:text-primary-300">{{ __('beneficiaries.import.include_group') }}</button>
                                                    <span class="text-gray-300 dark:text-white/20">·</span>
                                                    <button type="button" wire:click="setGroupIncluded(@js($groupValue), false)" class="text-xs font-medium text-gray-500 hover:underline dark:text-gray-400">{{ __('beneficiaries.import.exclude_group') }}</button>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endif

                                @foreach ($indexes as $i)
                                    @php
                                        $row = $rows[$i];
                                        $state = $states[$i] ?? null;
                                        $status = $state['status'] ?? null;
                                        $isDuplicate = in_array($status, ['duplicate_system', 'duplicate_file'], true);
                                    @endphp
                                    <tr
                                        wire:key="row-{{ $i }}"
                                        @class([
                                            'transition-colors',
                                            'bg-status-rejected/5' => $status === 'error',
                                            'bg-status-review/5' => $status === 'duplicate_system',
                                            'opacity-60' => $status === 'excluded' || $status === 'duplicate_file',
                                        ])
                                    >
                                        {{-- Include toggle --}}
                                        <td class="sticky start-0 z-10 bg-white px-3 py-2 text-center dark:bg-primary-950/40">
                                            @if ($isDuplicate)
                                                <input type="checkbox" disabled class="h-4 w-4 rounded border-gray-300 opacity-50 dark:border-white/20" title="{{ $statusStyles[$status]['label'] }}" />
                                            @else
                                                <input
                                                    type="checkbox"
                                                    wire:model.live="included.{{ $i }}"
                                                    class="h-4 w-4 rounded border-gray-300 dark:border-white/20"
                                                    style="accent-color: var(--color-primary);"
                                                    aria-label="{{ __('beneficiaries.import.col_include') }}"
                                                />
                                            @endif
                                        </td>

                                        {{-- Status --}}
                                        <td class="whitespace-nowrap px-3 py-2 align-top">
                                            @if ($ready && $state)
                                                <x-ui.badge :color="$statusStyles[$status]['color']">
                                                    <span class="inline-flex items-center gap-1">
                                                        <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                                                        {{ $statusStyles[$status]['label'] }}
                                                    </span>
                                                </x-ui.badge>
                                                @if ($status === 'error' && ! empty($state['error']))
                                                    <p class="mt-1 max-w-56 text-xs text-status-rejected">{{ $state['error'] }}</p>
                                                @endif
                                            @else
                                                <span class="text-xs text-gray-400">{{ __('beneficiaries.import.status_unmapped') }}</span>
                                            @endif
                                            <p class="mt-1 text-[11px] text-gray-400">{{ __('beneficiaries.import.col_line') }} {{ $row['line'] }}</p>
                                        </td>

                                        {{-- Data cells: editable when the column is mapped --}}
                                        @foreach ($headers as $col => $label)
                                            @php $isMapped = ($columnMapping[$col] ?? '') !== ''; @endphp
                                            <td wire:key="cell-{{ $i }}-{{ $col }}" class="px-2 py-1.5 align-top">
                                                @if ($isMapped)
                                                    <input
                                                        type="text"
                                                        wire:model.blur="rows.{{ $i }}.cells.{{ $col }}"
                                                        @class([
                                                            'block w-full min-w-40 rounded-md border bg-white px-2 py-1.5 text-xs text-gray-900 shadow-sm transition focus:outline-none focus:ring-2 focus:ring-primary-500/30 dark:bg-primary-950/30 dark:text-gray-100',
                                                            'border-status-rejected/60 focus:border-status-rejected' => $status === 'error',
                                                            'border-gray-200 focus:border-primary-500 dark:border-white/10' => $status !== 'error',
                                                        ])
                                                    />
                                                @else
                                                    <span class="block min-w-32 truncate px-1 py-1.5 text-xs text-gray-400 dark:text-gray-500" title="{{ $row['cells'][$col] ?? '' }}">{{ $row['cells'][$col] ?? '' }}</span>
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-2">
                    <x-ui.button variant="ghost" wire:click="startOver">
                        {{ __('beneficiaries.import.start_over') }}
                    </x-ui.button>
                    <x-ui.button variant="primary" wire:click="import" wire:target="import" wire:loading.attr="disabled" :disabled="! $ready">
                        <svg wire:loading.remove wire:target="import" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                        @if ($ready)
                            {{ __('beneficiaries.import.run_button_count', ['count' => $stats['valid']]) }}
                        @else
                            {{ __('beneficiaries.import.run_button') }}
                        @endif
                    </x-ui.button>
                </div>
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

                <div class="grid grid-cols-2 gap-4 lg:grid-cols-5">
                    <x-ui.stat-card :label="__('beneficiaries.import.stat_created')" :value="$summary['created']" />
                    <x-ui.stat-card :label="__('beneficiaries.import.stat_duplicates')" :value="$summary['duplicates']" />
                    <x-ui.stat-card :label="__('beneficiaries.import.stat_file_duplicates')" :value="$summary['file_duplicates']" />
                    <x-ui.stat-card :label="__('beneficiaries.import.stat_excluded')" :value="$summary['excluded']" />
                    <x-ui.stat-card :label="__('beneficiaries.import.stat_errors')" :value="count($summary['errors'])" />
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

@php
    $typeBadgeColors = [
        \App\Enums\AidProgramType::Cash->value => 'primary',
        \App\Enums\AidProgramType::InKind->value => 'accent',
        \App\Enums\AidProgramType::Both->value => 'secondary',
    ];
@endphp

<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ __('aid_programs.index_title') }}</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('aid_programs.index_subtitle') }}</p>
        </div>

        @can('manage', \App\Models\AidProgram::class)
            <x-ui.button
                type="button"
                variant="primary"
                x-on:click="$dispatch('openModal', { component: 'settings.aid-programs.form-modal' })"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                {{ __('aid_programs.create_button') }}
            </x-ui.button>
        @endcan
    </div>

    <x-ui.card>
        <x-slot:header>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ trans_choice('aid_programs.count_label', $this->programs->count(), ['count' => $this->programs->count()]) }}
                </p>

                <div class="relative sm:w-72">
                    <div class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3 text-gray-400 dark:text-gray-500">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                        </svg>
                    </div>
                    <x-ui.input
                        name="search"
                        wire:model.live.debounce.300ms="search"
                        :placeholder="__('aid_programs.search_placeholder')"
                        :aria-label="__('common.search')"
                        class="!ps-9"
                    />
                </div>
            </div>
        </x-slot:header>

        <div class="relative">
            <div wire:loading.flex wire:target="search" class="hidden flex-col gap-2" style="display: none">
                <x-ui.skeleton height="3rem" />
                <x-ui.skeleton height="3rem" />
                <x-ui.skeleton height="3rem" />
            </div>

            <div wire:loading.remove wire:target="search">
                @if ($this->programs->isEmpty())
                    <x-ui.empty-state
                        :title="$search !== '' ? __('aid_programs.empty_search_title') : __('aid_programs.empty_title')"
                        :description="$search !== '' ? __('aid_programs.empty_search_description') : __('aid_programs.empty_description')"
                    >
                        <x-slot:icon>
                            <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5m6 4.125 2.25 2.25m0 0 2.25-2.25m-2.25 2.25V6.75m-8.25.75h16.5m-9-3H12a2.25 2.25 0 0 0-2.25 2.25v.75h4.5v-.75A2.25 2.25 0 0 0 12 3.75Z" />
                            </svg>
                        </x-slot:icon>
                    </x-ui.empty-state>
                @else
                    <x-ui.table>
                        <thead>
                            <tr>
                                <x-ui.table.th>{{ __('aid_programs.field_name') }}</x-ui.table.th>
                                <x-ui.table.th>{{ __('aid_programs.field_type') }}</x-ui.table.th>
                                <x-ui.table.th>{{ __('aid_programs.field_approval_flow') }}</x-ui.table.th>
                                <x-ui.table.th align="end">{{ __('aid_programs.field_aids_count') }}</x-ui.table.th>
                                <x-ui.table.th>{{ __('aid_programs.field_active') }}</x-ui.table.th>
                                <x-ui.table.th align="end">{{ __('common.actions') }}</x-ui.table.th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                            @foreach ($this->programs as $program)
                                <tr wire:key="aid-program-{{ $program->id }}" class="transition-colors duration-150 ease-out hover:bg-gray-50 dark:hover:bg-white/5">
                                    <x-ui.table.td>
                                        <div class="flex items-center gap-3">
                                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary-50 text-primary-600 dark:bg-primary-900/40 dark:text-primary-300">
                                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5m6 4.125 2.25 2.25m0 0 2.25-2.25m-2.25 2.25V6.75m-8.25.75h16.5m-9-3H12a2.25 2.25 0 0 0-2.25 2.25v.75h4.5v-.75A2.25 2.25 0 0 0 12 3.75Z" />
                                                </svg>
                                            </span>
                                            <div class="min-w-0">
                                                <p class="font-semibold text-gray-900 dark:text-white">{{ $program->name }}</p>
                                                @if ($program->description)
                                                    <p class="mt-0.5 max-w-xs truncate text-xs text-gray-400 dark:text-gray-500">{{ $program->description }}</p>
                                                @endif
                                            </div>
                                        </div>
                                    </x-ui.table.td>
                                    <x-ui.table.td>
                                        <x-ui.badge :color="$typeBadgeColors[$program->type->value] ?? 'gray'">
                                            {{ $program->type->label() }}
                                        </x-ui.badge>
                                    </x-ui.table.td>
                                    <x-ui.table.td>
                                        @if ($program->approvalFlow)
                                            <span class="inline-flex items-center gap-1.5 text-gray-600 dark:text-gray-300">
                                                <svg class="h-4 w-4 shrink-0 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12M8.25 17.25h12M3.75 6.75h.007v.008H3.75V6.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3.75 12h.007v.008H3.75V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-.375 5.25h.007v.008H3.75v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                                                </svg>
                                                {{ $program->approvalFlow->name }}
                                            </span>
                                        @else
                                            <x-ui.badge color="gray">{{ __('aid_programs.default_flow') }}</x-ui.badge>
                                        @endif
                                    </x-ui.table.td>
                                    <x-ui.table.td align="end">
                                        <span class="inline-flex min-w-8 items-center justify-center rounded-full bg-gray-100 px-2 py-1 text-xs font-semibold tabular-nums text-gray-700 dark:bg-white/10 dark:text-gray-200">
                                            {{ $program->aids_count ?? $program->aids->count() }}
                                        </span>
                                    </x-ui.table.td>
                                    <x-ui.table.td>
                                        @can('manage', $program)
                                            <button
                                                type="button"
                                                wire:click="toggleActive({{ $program->id }})"
                                                wire:loading.class="animate-pulse"
                                                class="rounded-full transition-opacity duration-150 ease-out hover:opacity-80 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-500"
                                            >
                                                <x-ui.badge :color="$program->is_active ? 'approved' : 'draft'">
                                                    {{ $program->is_active ? __('common.active') : __('common.inactive') }}
                                                </x-ui.badge>
                                            </button>
                                        @else
                                            <x-ui.badge :color="$program->is_active ? 'approved' : 'draft'">
                                                {{ $program->is_active ? __('common.active') : __('common.inactive') }}
                                            </x-ui.badge>
                                        @endcan
                                    </x-ui.table.td>
                                    <x-ui.table.td align="end">
                                        <div class="flex items-center justify-end gap-1">
                                            @can('manage', $program)
                                                <x-ui.button
                                                    type="button"
                                                    x-on:click="$dispatch('openModal', { component: 'settings.aid-programs.form-modal', arguments: { program: {{ $program->id }} } })"
                                                    variant="ghost"
                                                    size="sm"
                                                    title="{{ __('common.edit') }}"
                                                    class="!text-gray-500 hover:!bg-gray-100 hover:!text-gray-700 dark:!text-gray-400 dark:hover:!bg-white/10 dark:hover:!text-gray-200"
                                                >
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                                    </svg>
                                                    <span class="sr-only">{{ __('common.edit') }}</span>
                                                </x-ui.button>

                                                <x-ui.button
                                                    variant="ghost"
                                                    size="sm"
                                                    data-confirm="{{ __('aid_programs.confirm_delete') }}"
                                                    x-on:click="uiConfirm($el.dataset.confirm, () => $wire.delete({{ $program->id }}), { danger: true })"
                                                    title="{{ __('common.delete') }}"
                                                    class="!text-gray-500 hover:!bg-status-rejected/10 hover:!text-status-rejected dark:!text-gray-400 dark:hover:!bg-status-rejected/15 dark:hover:!text-status-rejected"
                                                >
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                                    </svg>
                                                    <span class="sr-only">{{ __('common.delete') }}</span>
                                                </x-ui.button>
                                            @endcan
                                        </div>
                                    </x-ui.table.td>
                                </tr>
                            @endforeach
                        </tbody>
                    </x-ui.table>
                @endif
            </div>
        </div>
    </x-ui.card>
</div>

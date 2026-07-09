@php
    $statusOptions = $this->statuses->mapWithKeys(fn ($status) => [$status->value => $status->label()]);
    $programOptions = $this->programs->mapWithKeys(fn ($program) => [$program->id => $program->name]);
    $typeOptions = collect(\App\Enums\AidType::cases())->mapWithKeys(fn ($type) => [$type->value => $type->label()]);
    $receiptOptions = collect($this->receiptStatuses)->mapWithKeys(fn ($status) => [$status->value => $status->label()]);
@endphp

<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ __('aids.index_title') }}</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('aids.index_subtitle') }}</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <x-ui.button href="{{ route('aids.recurring-plans.index') }}" variant="ghost">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                </svg>
                {{ __('recurring_aids.nav_button') }}
            </x-ui.button>

            @can('create', \App\Models\Aid::class)
                <x-ui.button href="{{ route('aids.create') }}" variant="primary">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    {{ __('aids.create_button') }}
                </x-ui.button>
            @endcan
        </div>
    </div>

    <x-ui.card>
        @if ($this->hasActiveFilters)
            <div class="mb-4 flex flex-wrap items-center gap-3">
                <x-ui.badge color="accent">{{ __('common.active_filters', ['count' => $this->activeFiltersCount]) }}</x-ui.badge>

                <x-ui.button variant="ghost" size="sm" wire:click="resetFilters">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.348 14.652 21 3m0 0h-5.25M21 3v5.25M3 21l5.652-5.652M3 21v-5.25M3 21h5.25" />
                    </svg>
                    {{ __('common.reset_filters') }}
                </x-ui.button>
            </div>
        @endif

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-ui.input
                :label="__('common.search')"
                name="search"
                wire:model.live.debounce.300ms="search"
                :placeholder="__('aids.search_placeholder')"
            />

            <x-ui.select
                :label="__('aids.filter_status')"
                name="statusFilter"
                wire:model.live="statusFilter"
                :placeholder="__('common.all')"
                :options="$statusOptions"
            />

            <x-ui.select
                :label="__('aids.filter_program')"
                name="programFilter"
                wire:model.live="programFilter"
                :placeholder="__('common.all')"
                :options="$programOptions"
            />

            <x-ui.select
                :label="__('aids.filter_type')"
                name="typeFilter"
                wire:model.live="typeFilter"
                :placeholder="__('common.all')"
                :options="$typeOptions"
            />

            <x-ui.select
                :label="__('aids.filter_receipt')"
                name="receiptFilter"
                wire:model.live="receiptFilter"
                :placeholder="__('common.all')"
                :options="$receiptOptions"
            />
        </div>
    </x-ui.card>

    <x-ui.card>
        <div class="mb-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ trans_choice('aids.results_count', $this->aids->total(), ['count' => $this->aids->total()]) }}
            </p>
        </div>

        <div class="relative">
        <div wire:loading.flex wire:target="search, statusFilter, programFilter, typeFilter, receiptFilter" class="hidden flex-col gap-2" style="display: none">
            <x-ui.skeleton height="3rem" />
            <x-ui.skeleton height="3rem" />
            <x-ui.skeleton height="3rem" />
            <x-ui.skeleton height="3rem" />
            <x-ui.skeleton height="3rem" />
        </div>

        <div wire:loading.remove wire:target="search, statusFilter, programFilter, typeFilter, receiptFilter">
            @if ($this->aids->isEmpty())
                <x-ui.empty-state :title="__('aids.empty_title')" :description="__('aids.empty_description')">
                    <x-slot:icon>
                        <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 0 1-2.25 2.25M16.5 7.5V18a2.25 2.25 0 0 0 2.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 0 0 2.25 2.25h13.5M6 7.5h3v3H6v-3Z" />
                        </svg>
                    </x-slot:icon>
                </x-ui.empty-state>
            @else
                <x-ui.table>
                    <thead>
                        <tr>
                            <x-ui.table.th>{{ __('aids.field_reference') }}</x-ui.table.th>
                            <x-ui.table.th>{{ __('aids.field_beneficiary') }}</x-ui.table.th>
                            <x-ui.table.th>{{ __('aids.field_program') }}</x-ui.table.th>
                            <x-ui.table.th>{{ __('aids.field_type') }}</x-ui.table.th>
                            <x-ui.table.th align="end">{{ __('aids.field_amount') }}</x-ui.table.th>
                            <x-ui.table.th>{{ __('aids.field_status') }}</x-ui.table.th>
                            <x-ui.table.th>{{ __('aids.field_current_stage') }}</x-ui.table.th>
                            <x-ui.table.th>{{ __('aids.field_created_at') }}</x-ui.table.th>
                            <x-ui.table.th align="end">{{ __('common.actions') }}</x-ui.table.th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                        @foreach ($this->aids as $aid)
                            <tr class="transition duration-150 hover:bg-gray-50 dark:hover:bg-white/5">
                                <x-ui.table.td>
                                    <a
                                        href="{{ route('aids.show', $aid) }}"
                                        wire:navigate
                                        class="font-mono font-medium text-primary-700 underline-offset-2 hover:underline dark:text-primary-300"
                                    >
                                        {{ $aid->reference }}
                                    </a>
                                </x-ui.table.td>
                                <x-ui.table.td class="text-gray-900 dark:text-white">{{ $aid->beneficiary?->full_name }}</x-ui.table.td>
                                <x-ui.table.td>{{ $aid->program?->name }}</x-ui.table.td>
                                <x-ui.table.td>
                                    <x-ui.badge color="accent">{{ $aid->type->label() }}</x-ui.badge>
                                </x-ui.table.td>
                                <x-ui.table.td align="end" class="tabular-nums">
                                    @if ($aid->type === \App\Enums\AidType::Cash)
                                        {{ number_format((float) $aid->amount, 2) }} {{ __('aids.currency_sar') }}
                                    @else
                                        {{ __('aids.items_count', ['count' => $aid->items_count ?? $aid->items->count()]) }}
                                    @endif
                                </x-ui.table.td>
                                <x-ui.table.td>
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <x-ui.badge :color="$aid->status->color()">{{ $aid->status->label() }}</x-ui.badge>
                                        @if ($aid->confirmation?->receipt_status?->needsAttention())
                                            <x-ui.badge :color="$aid->confirmation->receipt_status->color()">
                                                {{ $aid->confirmation->receipt_status->label() }}
                                            </x-ui.badge>
                                        @endif
                                    </div>
                                </x-ui.table.td>
                                <x-ui.table.td>
                                    @if ($aid->currentStage)
                                        <span class="text-status-review">{{ $aid->currentStage->name }}</span>
                                    @else
                                        <span class="text-gray-400 dark:text-gray-500">{{ __('common.dash') }}</span>
                                    @endif
                                </x-ui.table.td>
                                <x-ui.table.td class="tabular-nums text-gray-500 dark:text-gray-400">
                                    {{ $aid->created_at?->translatedFormat('Y/m/d') }}
                                </x-ui.table.td>
                                <x-ui.table.td align="end">
                                    <div class="flex items-center justify-end gap-1">
                                        @can('view', $aid)
                                            <x-ui.button href="{{ route('aids.show', $aid) }}" variant="ghost" size="sm">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                                </svg>
                                                <span class="sr-only">{{ __('common.view') }}</span>
                                            </x-ui.button>
                                        @endcan

                                        @if ($aid->status->isEditable())
                                            @can('update', $aid)
                                                <x-ui.button href="{{ route('aids.edit', $aid) }}" variant="ghost" size="sm">
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                                    </svg>
                                                    <span class="sr-only">{{ __('common.edit') }}</span>
                                                </x-ui.button>
                                            @endcan
                                        @endif

                                        @can('cancel', $aid)
                                            <x-ui.button
                                                variant="ghost"
                                                size="sm"
                                                data-confirm="{{ __('aids.confirm_cancel') }}"
                                                x-on:click="uiConfirm($el.dataset.confirm, () => $wire.cancel({{ $aid->id }}), { danger: true })"
                                            >
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636" />
                                                </svg>
                                                <span class="sr-only">{{ __('common.cancel') }}</span>
                                            </x-ui.button>
                                        @endcan

                                        @can('delete', $aid)
                                            <x-ui.button
                                                variant="danger"
                                                size="sm"
                                                data-confirm="{{ __('aids.confirm_delete') }}"
                                                x-on:click="uiConfirm($el.dataset.confirm, () => $wire.delete({{ $aid->id }}), { danger: true })"
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

                <div class="mt-4">
                    {{ $this->aids->links() }}
                </div>
            @endif
        </div>
        </div>
    </x-ui.card>
</div>

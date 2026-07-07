@php
    $statusOptions = collect($this->statuses)->mapWithKeys(fn ($status) => [$status->value => $status->label()]);
    $programOptions = $this->programs->mapWithKeys(fn ($program) => [$program->id => $program->name]);
    $typeOptions = collect($this->types)->mapWithKeys(fn ($type) => [$type->value => $type->label()]);
@endphp

<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ __('reports.aids.title') }}</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('reports.aids.subtitle') }}</p>
        </div>

        @can('reports.export')
            <div class="flex items-center gap-2">
                <x-ui.button wire:click="exportExcel" variant="secondary" size="sm">
                    {{ __('reports.actions.export_excel') }}
                </x-ui.button>
                <x-ui.button wire:click="exportPdf" variant="ghost" size="sm">
                    {{ __('reports.actions.export_pdf') }}
                </x-ui.button>
            </div>
        @endcan
    </div>

    <x-ui.card>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-6">
            <x-ui.input :label="__('reports.filters.from')" name="from" type="date" wire:model.live="from" />
            <x-ui.input :label="__('reports.filters.to')" name="to" type="date" wire:model.live="to" />

            <x-ui.select
                :label="__('reports.filters.status')"
                name="status"
                wire:model.live="status"
                :placeholder="__('common.all')"
                :options="$statusOptions"
            />

            <x-ui.select
                :label="__('reports.filters.program')"
                name="program"
                wire:model.live="program"
                :placeholder="__('common.all')"
                :options="$programOptions"
            />

            <x-ui.select
                :label="__('reports.filters.type')"
                name="type"
                wire:model.live="type"
                :placeholder="__('common.all')"
                :options="$typeOptions"
            />

            <x-ui.select
                :label="__('reports.filters.delivery_method')"
                name="delivery"
                :hint="__('reports.filters.delivery_method_hint')"
                disabled
                :options="[]"
            />
        </div>
    </x-ui.card>

    <x-ui.card>
        <x-slot:header>
            <div class="flex flex-wrap items-center gap-x-6 gap-y-1 text-sm font-medium text-gray-700 dark:text-gray-200">
                <span>{{ __('reports.aids.total_count', ['count' => $this->totals['count']]) }}</span>
                <span class="text-secondary-700 dark:text-secondary-300">
                    {{ __('reports.aids.total_cash', ['amount' => number_format((float) $this->totals['cash_total'], 2)]) }}
                </span>
                <span class="text-accent-700 dark:text-accent-300">
                    {{ __('reports.aids.total_in_kind', ['amount' => number_format((float) $this->totals['in_kind_total'], 2)]) }}
                </span>
            </div>
        </x-slot:header>

        <div wire:loading.flex wire:target="from, to, status, program, type" class="hidden flex-col gap-2" style="display: none">
            <x-ui.skeleton height="3rem" />
            <x-ui.skeleton height="3rem" />
            <x-ui.skeleton height="3rem" />
        </div>

        <div wire:loading.remove wire:target="from, to, status, program, type" class="space-y-4">
            @include('livewire.reports.partials.preview-table', [
                'headings' => $this->report->headings(),
                'mappedRows' => $this->rows->getCollection()->map(fn ($row) => $this->report->map($row)),
            ])

            <div>{{ $this->rows->links() }}</div>
        </div>
    </x-ui.card>
</div>

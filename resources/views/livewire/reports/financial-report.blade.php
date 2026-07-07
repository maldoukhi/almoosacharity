@php
    $programOptions = $this->programs->mapWithKeys(fn ($program) => [$program->id => $program->name]);
@endphp

<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ __('reports.financial.title') }}</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('reports.financial.subtitle') }}</p>
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
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <x-ui.input :label="__('reports.filters.from')" name="from" type="date" wire:model.live="from" />
            <x-ui.input :label="__('reports.filters.to')" name="to" type="date" wire:model.live="to" />

            <x-ui.select
                :label="__('reports.filters.program')"
                name="program"
                wire:model.live="program"
                :placeholder="__('common.all')"
                :options="$programOptions"
            />
        </div>
    </x-ui.card>

    <x-ui.card>
        <x-slot:header>
            <div class="flex flex-wrap items-center gap-x-6 gap-y-1 text-sm font-medium text-gray-700 dark:text-gray-200">
                <span>{{ __('reports.financial.total_count', ['count' => $this->totals['count']]) }}</span>
                <span class="text-secondary-700 dark:text-secondary-300">
                    {{ __('reports.financial.total_cash', ['amount' => number_format((float) $this->totals['cash_total'], 2)]) }}
                </span>
                <span class="text-accent-700 dark:text-accent-300">
                    {{ __('reports.financial.total_in_kind', ['amount' => number_format((float) $this->totals['in_kind_total'], 2)]) }}
                </span>
                <span class="font-semibold text-primary-800 dark:text-primary-200">
                    {{ __('reports.financial.total_grand', ['amount' => number_format((float) $this->totals['grand_total'], 2)]) }}
                </span>
            </div>
        </x-slot:header>

        <div wire:loading.flex wire:target="from, to, program" class="hidden flex-col gap-2" style="display: none">
            <x-ui.skeleton height="3rem" />
            <x-ui.skeleton height="3rem" />
            <x-ui.skeleton height="3rem" />
        </div>

        <div wire:loading.remove wire:target="from, to, program">
            @include('livewire.reports.partials.preview-table', [
                'headings' => $this->report->headings(),
                'mappedRows' => $this->rows->map(fn ($row) => $this->report->map($row)),
            ])
        </div>
    </x-ui.card>
</div>

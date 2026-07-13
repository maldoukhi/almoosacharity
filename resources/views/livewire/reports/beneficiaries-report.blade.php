@php
    $statusOptions = collect($this->statuses)->mapWithKeys(fn ($status) => [$status->value => $status->label()]);
    $categoryOptions = $this->categories->mapWithKeys(fn ($category) => [$category->id => $category->name]);
    $cityOptions = collect($this->cities)->mapWithKeys(fn ($city) => [$city => $city]);
@endphp

<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ __('reports.beneficiaries.title') }}</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('reports.beneficiaries.subtitle') }}</p>
        </div>

        <div class="flex items-center gap-2">
            <x-ui.help-link section="reports" />

            @can('reports.export')
                <x-ui.button wire:click="exportExcel" variant="secondary" size="sm">
                    {{ __('reports.actions.export_excel') }}
                </x-ui.button>
                <x-ui.button wire:click="exportPdf" variant="ghost" size="sm">
                    {{ __('reports.actions.export_pdf') }}
                </x-ui.button>
            @endcan
        </div>
    </div>

    <x-ui.card>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
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
                :label="__('beneficiaries.filter_category')"
                name="category"
                wire:model.live="category"
                :placeholder="__('common.all')"
                :options="$categoryOptions"
            />

            <x-ui.select
                :label="__('reports.filters.city')"
                name="city"
                wire:model.live="city"
                :placeholder="__('common.all')"
                :options="$cityOptions"
            />
        </div>
    </x-ui.card>

    <x-ui.card>
        <x-slot:header>
            <span class="text-sm font-medium text-gray-700 dark:text-gray-200">
                {{ __('reports.beneficiaries.total_count', ['count' => $this->totals['count']]) }}
            </span>
        </x-slot:header>

        <div wire:loading.flex wire:target="from, to, status, category, city" class="hidden flex-col gap-2" style="display: none">
            <x-ui.skeleton height="3rem" />
            <x-ui.skeleton height="3rem" />
            <x-ui.skeleton height="3rem" />
        </div>

        <div wire:loading.remove wire:target="from, to, status, category, city" class="space-y-4">
            @include('livewire.reports.partials.preview-table', [
                'headings' => $this->report->headings(),
                'mappedRows' => $this->rows->getCollection()->map(fn ($row) => $this->report->map($row)),
            ])

            <div>{{ $this->rows->links() }}</div>
        </div>
    </x-ui.card>
</div>

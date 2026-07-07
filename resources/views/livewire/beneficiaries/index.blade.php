@php
    $categoryOptions = $this->categories->mapWithKeys(fn ($category) => [$category->id => $category->name]);

    $statusOptions = collect(\App\Enums\BeneficiaryStatus::cases())->mapWithKeys(fn ($status) => [
        $status->value => $status->label(),
    ]);

    $cityOptions = collect($this->cities)->mapWithKeys(fn ($city) => [$city => $city]);
@endphp

<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ __('beneficiaries.index_title') }}</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('beneficiaries.index_subtitle') }}</p>
        </div>

        @can('create', \App\Models\Beneficiary::class)
            <x-ui.button href="{{ route('admin.beneficiaries.create') }}" variant="primary">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                {{ __('beneficiaries.create_button') }}
            </x-ui.button>
        @endcan
    </div>

    <x-ui.card>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-ui.input
                :label="__('common.search')"
                name="search"
                wire:model.live.debounce.300ms="search"
                :placeholder="__('beneficiaries.search_placeholder')"
            />

            <x-ui.select
                :label="__('beneficiaries.filter_category')"
                name="categoryFilter"
                wire:model.live="categoryFilter"
                :placeholder="__('common.all')"
                :options="$categoryOptions"
            />

            <x-ui.select
                :label="__('beneficiaries.filter_status')"
                name="statusFilter"
                wire:model.live="statusFilter"
                :placeholder="__('common.all')"
                :options="$statusOptions"
            />

            <x-ui.select
                :label="__('beneficiaries.filter_city')"
                name="cityFilter"
                wire:model.live="cityFilter"
                :placeholder="__('common.all')"
                :options="$cityOptions"
            />
        </div>

        @can('restore', \App\Models\Beneficiary::class)
            <div class="mt-4 flex items-center justify-end border-t border-gray-100 pt-4 dark:border-white/10">
                <label class="flex cursor-pointer items-center gap-3 select-none">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('beneficiaries.show_trashed') }}</span>
                    <span class="relative inline-block h-6 w-11 shrink-0">
                        <input type="checkbox" wire:model.live="trashed" class="peer sr-only" />
                        <span class="absolute inset-0 rounded-full bg-gray-200 transition-colors duration-200 ease-out peer-checked:bg-primary dark:bg-white/10"></span>
                        <span class="absolute start-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow transition-transform duration-200 ease-out peer-checked:translate-x-5 rtl:peer-checked:-translate-x-5"></span>
                    </span>
                </label>
            </div>
        @endcan
    </x-ui.card>

    <div class="relative">
        <div wire:loading.flex wire:target="search, categoryFilter, statusFilter, cityFilter, trashed" class="hidden flex-col gap-2" style="display: none">
            <x-ui.skeleton height="3rem" />
            <x-ui.skeleton height="3rem" />
            <x-ui.skeleton height="3rem" />
            <x-ui.skeleton height="3rem" />
            <x-ui.skeleton height="3rem" />
        </div>

        <div wire:loading.remove wire:target="search, categoryFilter, statusFilter, cityFilter, trashed">
            @if ($this->beneficiaries->isEmpty())
                <x-ui.empty-state :title="__('beneficiaries.empty_title')" :description="__('beneficiaries.empty_description')">
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
                            <x-ui.table.th>{{ __('beneficiaries.field_full_name') }}</x-ui.table.th>
                            <x-ui.table.th>{{ __('beneficiaries.field_national_id') }}</x-ui.table.th>
                            <x-ui.table.th>{{ __('beneficiaries.field_mobile') }}</x-ui.table.th>
                            <x-ui.table.th>{{ __('beneficiaries.field_city') }}</x-ui.table.th>
                            <x-ui.table.th>{{ __('beneficiaries.field_categories') }}</x-ui.table.th>
                            <x-ui.table.th>{{ __('beneficiaries.field_status') }}</x-ui.table.th>
                            <x-ui.table.th align="end">{{ __('common.actions') }}</x-ui.table.th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                        @foreach ($this->beneficiaries as $beneficiary)
                            <tr class="transition duration-150 hover:bg-gray-50 dark:hover:bg-white/5">
                                <x-ui.table.td class="font-medium text-gray-900 dark:text-white">
                                    {{ $beneficiary->full_name }}
                                </x-ui.table.td>
                                <x-ui.table.td class="tabular-nums" dir="ltr">{{ $beneficiary->national_id }}</x-ui.table.td>
                                <x-ui.table.td class="tabular-nums" dir="ltr">{{ $beneficiary->mobile ?: __('common.dash') }}</x-ui.table.td>
                                <x-ui.table.td>{{ $beneficiary->city ?: __('common.dash') }}</x-ui.table.td>
                                <x-ui.table.td>
                                    <div class="flex flex-wrap gap-1">
                                        @forelse ($beneficiary->categories as $category)
                                            <x-ui.badge color="accent">{{ $category->name }}</x-ui.badge>
                                        @empty
                                            <span class="text-gray-400 dark:text-gray-500">{{ __('common.dash') }}</span>
                                        @endforelse
                                    </div>
                                </x-ui.table.td>
                                <x-ui.table.td>
                                    <x-ui.badge :color="$beneficiary->status->color()">
                                        {{ $beneficiary->status->label() }}
                                    </x-ui.badge>
                                </x-ui.table.td>
                                <x-ui.table.td align="end">
                                    <div class="flex items-center justify-end gap-1">
                                        @if ($trashed)
                                            @can('restore', $beneficiary)
                                                <x-ui.button
                                                    variant="ghost"
                                                    size="sm"
                                                    wire:click="restore({{ $beneficiary->id }})"
                                                    wire:confirm="{{ __('beneficiaries.confirm_restore') }}"
                                                >
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3" />
                                                    </svg>
                                                    <span class="sr-only">{{ __('common.restore') }}</span>
                                                </x-ui.button>
                                            @endcan
                                        @else
                                            @can('view', $beneficiary)
                                                <x-ui.button href="{{ route('admin.beneficiaries.show', $beneficiary) }}" variant="ghost" size="sm">
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                                    </svg>
                                                    <span class="sr-only">{{ __('common.view') }}</span>
                                                </x-ui.button>
                                            @endcan

                                            @can('update', $beneficiary)
                                                <x-ui.button href="{{ route('admin.beneficiaries.edit', $beneficiary) }}" variant="ghost" size="sm">
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                                    </svg>
                                                    <span class="sr-only">{{ __('common.edit') }}</span>
                                                </x-ui.button>
                                            @endcan

                                            @can('delete', $beneficiary)
                                                <x-ui.button
                                                    variant="danger"
                                                    size="sm"
                                                    wire:click="delete({{ $beneficiary->id }})"
                                                    wire:confirm="{{ __('beneficiaries.confirm_delete') }}"
                                                >
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                                    </svg>
                                                    <span class="sr-only">{{ __('common.delete') }}</span>
                                                </x-ui.button>
                                            @endcan
                                        @endif
                                    </div>
                                </x-ui.table.td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-ui.table>

                <div class="mt-4">
                    {{ $this->beneficiaries->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

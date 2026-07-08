@php
    $isEdit = ! is_null($editingId);
@endphp

<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ __('beneficiaries.categories.index_title') }}</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('beneficiaries.categories.index_subtitle') }}</p>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <x-ui.card class="lg:col-span-1">
            <h2 class="mb-4 text-sm font-semibold text-gray-900 dark:text-white">
                {{ $isEdit ? __('beneficiaries.categories.form_title_edit') : __('beneficiaries.categories.form_title_create') }}
            </h2>

            <form wire:submit="save" class="space-y-4">
                <x-ui.input :label="__('beneficiaries.categories.field_name')" name="name" wire:model="name" />

                <div>
                    <label for="description" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">
                        {{ __('beneficiaries.categories.field_description') }}
                    </label>
                    <textarea
                        id="description"
                        wire:model="description"
                        rows="3"
                        class="block w-full rounded-(--radius-brand) border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 shadow-sm transition duration-200 ease-out focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30 dark:border-white/10 dark:bg-primary-950/30 dark:text-gray-100"
                    ></textarea>
                    @error('description')
                        <p class="mt-1.5 text-xs text-status-rejected">{{ $message }}</p>
                    @enderror
                </div>

                <label class="flex cursor-pointer items-center gap-3 select-none">
                    <span class="relative inline-block h-6 w-11 shrink-0">
                        <input type="checkbox" wire:model="is_active" class="peer sr-only" />
                        <span class="absolute inset-0 rounded-full bg-gray-200 transition-colors duration-200 ease-out peer-checked:bg-primary dark:bg-white/10"></span>
                        <span class="absolute start-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow transition-transform duration-200 ease-out peer-checked:translate-x-5 rtl:peer-checked:-translate-x-5"></span>
                    </span>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('beneficiaries.categories.field_is_active') }}</span>
                </label>

                <div class="flex items-center gap-3 border-t border-gray-100 pt-4 dark:border-white/10">
                    <x-ui.button type="submit" variant="primary" wire:target="save">{{ __('common.save') }}</x-ui.button>

                    @if ($isEdit)
                        <x-ui.button type="button" variant="ghost" wire:click="cancel">{{ __('common.cancel') }}</x-ui.button>
                    @endif
                </div>
            </form>
        </x-ui.card>

        <x-ui.card class="lg:col-span-2">
            <div class="mb-4">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ trans_choice('beneficiaries.categories.results_count', $this->categories->count(), ['count' => $this->categories->count()]) }}
                </p>
            </div>

            @if ($this->categories->isEmpty())
                <x-ui.empty-state :title="__('beneficiaries.categories.empty_title')" :description="__('beneficiaries.categories.empty_description')" />
            @else
                <x-ui.table>
                    <thead>
                        <tr>
                            <x-ui.table.th>{{ __('beneficiaries.categories.field_name') }}</x-ui.table.th>
                            <x-ui.table.th>{{ __('beneficiaries.categories.field_description') }}</x-ui.table.th>
                            <x-ui.table.th align="center">{{ __('beneficiaries.categories.table_beneficiary_count') }}</x-ui.table.th>
                            <x-ui.table.th>{{ __('beneficiaries.categories.field_is_active') }}</x-ui.table.th>
                            <x-ui.table.th align="end">{{ __('common.actions') }}</x-ui.table.th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                        @foreach ($this->categories as $category)
                            <tr wire:key="category-{{ $category->id }}" class="transition duration-150 hover:bg-gray-50 dark:hover:bg-white/5">
                                <x-ui.table.td class="font-medium text-gray-900 dark:text-white">{{ $category->name }}</x-ui.table.td>
                                <x-ui.table.td class="max-w-xs truncate">{{ $category->description ?: __('common.dash') }}</x-ui.table.td>
                                <x-ui.table.td align="center" class="tabular-nums">{{ $category->beneficiaries_count ?? $category->beneficiaries()->count() }}</x-ui.table.td>
                                <x-ui.table.td>
                                    <button type="button" wire:click="toggleActive({{ $category->id }})">
                                        <x-ui.badge :color="$category->is_active ? 'approved' : 'gray'">
                                            {{ $category->is_active ? __('beneficiaries.categories.status_active') : __('beneficiaries.categories.status_inactive') }}
                                        </x-ui.badge>
                                    </button>
                                </x-ui.table.td>
                                <x-ui.table.td align="end">
                                    <div class="flex items-center justify-end gap-1">
                                        <x-ui.button type="button" variant="ghost" size="sm" wire:click="edit({{ $category->id }})">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                            </svg>
                                            <span class="sr-only">{{ __('common.edit') }}</span>
                                        </x-ui.button>

                                        <x-ui.button
                                            type="button"
                                            variant="danger"
                                            size="sm"
                                            data-confirm="{{ __('beneficiaries.categories.confirm_delete') }}"
                                            x-on:click="uiConfirm($el.dataset.confirm, () => $wire.delete({{ $category->id }}), { danger: true })"
                                        >
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                            </svg>
                                            <span class="sr-only">{{ __('common.delete') }}</span>
                                        </x-ui.button>
                                    </div>
                                </x-ui.table.td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-ui.table>
            @endif
        </x-ui.card>
    </div>
</div>

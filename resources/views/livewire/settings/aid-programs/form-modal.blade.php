@php
    $isEdit = $program?->exists ?? false;

    $typeOptions = $this->types->mapWithKeys(fn ($type) => [$type->value => $type->label()]);
    $flowOptions = $this->flows->mapWithKeys(fn ($flow) => [$flow->id => $flow->name]);
@endphp

<div class="p-6">
    <div class="mb-5">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
            {{ $isEdit ? __('aid_programs.edit_title') : __('aid_programs.create_title') }}
        </h2>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            {{ $isEdit ? __('aid_programs.edit_subtitle') : __('aid_programs.create_subtitle') }}
        </p>
    </div>

    <form wire:submit="save" class="space-y-5">
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <x-ui.input :label="__('aid_programs.field_name')" name="name" wire:model="name" autofocus />

            <x-ui.select
                :label="__('aid_programs.field_type')"
                name="type"
                wire:model="type"
                :options="$typeOptions"
            />

            <x-ui.select
                :label="__('aid_programs.field_approval_flow')"
                name="approval_flow_id"
                wire:model="approval_flow_id"
                :placeholder="__('aid_programs.default_flow')"
                :options="$flowOptions"
            />

            <x-ui.input :label="__('aid_programs.field_sort_order')" name="sort_order" type="number" min="0" wire:model="sort_order" />
        </div>

        <div>
            <label for="description" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">
                {{ __('aid_programs.field_description') }}
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
            <span class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('aid_programs.field_is_active') }}</span>
        </label>

        <div class="flex items-center justify-end gap-3 border-t border-gray-100 pt-5 dark:border-white/10">
            <x-ui.button type="button" variant="ghost" wire:click="closeModal">
                {{ __('common.cancel') }}
            </x-ui.button>

            <x-ui.button type="submit" variant="primary" wire:target="save">
                {{ __('common.save') }}
            </x-ui.button>
        </div>
    </form>
</div>

@php
    $isEdit = $memberId !== null;
    $relationOptions = collect($this->relations)->mapWithKeys(fn ($case) => [$case->value => $case->label()]);
@endphp

<div class="p-6">
    <div class="mb-5">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
            {{ $isEdit ? __('beneficiaries.family.form_title_edit') : __('beneficiaries.family.form_title_create') }}
        </h2>
    </div>

    <form wire:submit="save" class="space-y-5">
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <x-ui.input :label="__('beneficiaries.family.field_name')" name="name" wire:model="name" autofocus />

            <x-ui.select
                :label="__('beneficiaries.family.field_relation')"
                name="relation"
                wire:model="relation"
                :placeholder="__('beneficiaries.select_placeholder')"
                :options="$relationOptions"
            />

            <x-ui.input :label="__('beneficiaries.family.field_birth_date')" name="birth_date" type="date" wire:model="birth_date" />
            <x-ui.input :label="__('beneficiaries.family.field_health_status')" name="health_status" wire:model="health_status" />
            <x-ui.input :label="__('beneficiaries.family.field_education_status')" name="education_status" wire:model="education_status" />
        </div>

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

@php
    $roleOptions = $this->roles->mapWithKeys(fn ($role) => [
        $role->name => \App\Enums\RoleName::tryFrom($role->name)?->label() ?? $role->name,
    ]);

    $statusOptions = collect(\App\Enums\UserStatus::cases())->mapWithKeys(fn ($status) => [
        $status->value => $status->label(),
    ]);

    $localeOptions = collect(\App\Enums\Locale::cases())->mapWithKeys(fn ($locale) => [
        $locale->value => $locale->label(),
    ]);

    $isEdit = $user?->exists ?? false;
@endphp

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">
                {{ $isEdit ? __('users.edit_title') : __('users.create_title') }}
            </h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                {{ $isEdit ? __('users.edit_subtitle') : __('users.create_subtitle') }}
            </p>
        </div>

        <x-ui.button href="{{ route('admin.users.index') }}" variant="ghost">
            {{ __('common.back') }}
        </x-ui.button>
    </div>

    <x-ui.card>
        <form wire:submit="save" class="space-y-5">
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <x-ui.input :label="__('users.field_name')" name="name" wire:model="name" autofocus />
                <x-ui.input :label="__('users.field_email')" name="email" type="email" wire:model="email" autocomplete="off" />
                <x-ui.input :label="__('users.field_phone')" name="phone" wire:model="phone" />
                <x-ui.input :label="__('users.field_job_title')" name="job_title" wire:model="job_title" />

                <x-ui.select
                    :label="__('users.field_role')"
                    name="role"
                    wire:model="role"
                    :placeholder="__('users.select_role')"
                    :options="$roleOptions"
                />

                <x-ui.select
                    :label="__('users.field_status')"
                    name="status"
                    wire:model="status"
                    :options="$statusOptions"
                />

                <x-ui.select
                    :label="__('users.field_preferred_locale')"
                    name="preferred_locale"
                    wire:model="preferred_locale"
                    :options="$localeOptions"
                />
            </div>

            <div class="grid grid-cols-1 gap-5 border-t border-gray-100 pt-5 sm:grid-cols-2 dark:border-white/10">
                <x-ui.input
                    :label="__('users.field_password')"
                    name="password"
                    type="password"
                    wire:model="password"
                    autocomplete="new-password"
                    :hint="$isEdit ? __('users.password_hint_keep') : null"
                />

                <x-ui.input
                    :label="__('users.field_password_confirmation')"
                    name="password_confirmation"
                    type="password"
                    wire:model="password_confirmation"
                    autocomplete="new-password"
                />
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-gray-100 pt-5 dark:border-white/10">
                <x-ui.button href="{{ route('admin.users.index') }}" variant="ghost">
                    {{ __('common.cancel') }}
                </x-ui.button>

                <x-ui.button type="submit" variant="primary" wire:target="save">
                    {{ __('common.save') }}
                </x-ui.button>
            </div>
        </form>
    </x-ui.card>
</div>

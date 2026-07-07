@php
    $isEdit = $role?->exists ?? false;
@endphp

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">
                {{ $isEdit ? __('roles.edit_title') : __('roles.create_title') }}
            </h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                {{ $isEdit ? __('roles.edit_subtitle') : __('roles.create_subtitle') }}
            </p>
        </div>

        <x-ui.button href="{{ route('admin.roles.index') }}" variant="ghost">
            {{ __('common.back') }}
        </x-ui.button>
    </div>

    <x-ui.card>
        <form wire:submit="save" class="space-y-6">
            <x-ui.input :label="__('roles.field_name')" name="name" wire:model="name" autofocus />

            <div class="space-y-5 border-t border-gray-100 pt-5 dark:border-white/10">
                <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-200">
                    {{ __('roles.field_permissions') }}
                </h2>

                @foreach ($this->permissionGroups as $group => $permissions)
                    <div class="rounded-(--radius-brand) border border-gray-100 p-4 dark:border-white/10">
                        <h3 class="mb-3 text-sm font-semibold text-primary-800 dark:text-primary-200">
                            {{ __('roles.groups.'.$group) }}
                        </h3>

                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach ($permissions as $permission)
                                <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                                    <input
                                        type="checkbox"
                                        wire:model="selectedPermissions"
                                        value="{{ $permission->name }}"
                                        class="rounded border-gray-300 text-primary focus:ring-2 focus:ring-primary-500/30 dark:border-white/20 dark:bg-transparent"
                                    >
                                    {{ __('roles.permissions.'.$permission->name) }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-gray-100 pt-5 dark:border-white/10">
                <x-ui.button href="{{ route('admin.roles.index') }}" variant="ghost">
                    {{ __('common.cancel') }}
                </x-ui.button>

                <x-ui.button type="submit" variant="primary" wire:target="save">
                    {{ __('common.save') }}
                </x-ui.button>
            </div>
        </form>
    </x-ui.card>
</div>

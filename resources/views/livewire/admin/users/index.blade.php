@php
    $roleOptions = $this->roles->mapWithKeys(fn ($role) => [
        $role->name => \App\Enums\RoleName::tryFrom($role->name)?->label() ?? $role->name,
    ]);

    $statusOptions = collect(\App\Enums\UserStatus::cases())->mapWithKeys(fn ($status) => [
        $status->value => $status->label(),
    ]);
@endphp

<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ __('users.index_title') }}</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('users.index_subtitle') }}</p>
        </div>

        @can('create', \App\Models\User::class)
            <x-ui.button
                type="button"
                variant="primary"
                x-on:click="$dispatch('openModal', { component: 'admin.users.form-modal' })"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                {{ __('users.create_button') }}
            </x-ui.button>
        @endcan
    </div>

    <x-ui.card>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <x-ui.input
                :label="__('common.search')"
                name="search"
                wire:model.live.debounce.300ms="search"
                :placeholder="__('users.search_placeholder')"
            />

            <x-ui.select
                :label="__('users.filter_role')"
                name="roleFilter"
                wire:model.live="roleFilter"
                :placeholder="__('common.all')"
                :options="$roleOptions"
            />

            <x-ui.select
                :label="__('users.filter_status')"
                name="statusFilter"
                wire:model.live="statusFilter"
                :placeholder="__('common.all')"
                :options="$statusOptions"
            />
        </div>
    </x-ui.card>

    <x-ui.card>
        <div class="mb-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ trans_choice('users.results_count', $this->users->total(), ['count' => $this->users->total()]) }}
            </p>
        </div>

        <div class="relative">
        <div wire:loading.flex wire:target="search, roleFilter, statusFilter" class="hidden flex-col gap-2" style="display: none">
            <x-ui.skeleton height="3rem" />
            <x-ui.skeleton height="3rem" />
            <x-ui.skeleton height="3rem" />
            <x-ui.skeleton height="3rem" />
            <x-ui.skeleton height="3rem" />
        </div>

        <div wire:loading.remove wire:target="search, roleFilter, statusFilter">
            @if ($this->users->isEmpty())
                <x-ui.empty-state :title="__('users.empty_title')" :description="__('users.empty_description')">
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
                            <x-ui.table.th>{{ __('users.field_name') }}</x-ui.table.th>
                            <x-ui.table.th>{{ __('users.field_email') }}</x-ui.table.th>
                            <x-ui.table.th>{{ __('users.field_phone') }}</x-ui.table.th>
                            <x-ui.table.th>{{ __('users.field_role') }}</x-ui.table.th>
                            <x-ui.table.th>{{ __('users.field_status') }}</x-ui.table.th>
                            <x-ui.table.th>{{ __('users.field_last_login') }}</x-ui.table.th>
                            <x-ui.table.th align="end">{{ __('common.actions') }}</x-ui.table.th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                        @foreach ($this->users as $user)
                            <tr class="transition duration-150 hover:bg-gray-50 dark:hover:bg-white/5">
                                <x-ui.table.td class="font-medium text-gray-900 dark:text-white">
                                    {{ $user->name }}
                                </x-ui.table.td>
                                <x-ui.table.td>{{ $user->email }}</x-ui.table.td>
                                <x-ui.table.td>{{ $user->phone ?: __('common.dash') }}</x-ui.table.td>
                                <x-ui.table.td>
                                    @php $userRole = $user->roles->first(); @endphp
                                    @if ($userRole)
                                        <x-ui.badge color="primary">
                                            {{ \App\Enums\RoleName::tryFrom($userRole->name)?->label() ?? $userRole->name }}
                                        </x-ui.badge>
                                    @else
                                        <span class="text-gray-400 dark:text-gray-500">{{ __('common.dash') }}</span>
                                    @endif
                                </x-ui.table.td>
                                <x-ui.table.td>
                                    <x-ui.badge :color="$user->status->color()">
                                        {{ $user->status->label() }}
                                    </x-ui.badge>
                                </x-ui.table.td>
                                <x-ui.table.td>
                                    {{ $user->last_login_at?->diffForHumans() ?? __('users.never_logged_in') }}
                                </x-ui.table.td>
                                <x-ui.table.td align="end">
                                    <div class="flex items-center justify-end gap-1">
                                        @can('update', $user)
                                            <x-ui.button
                                                type="button"
                                                x-on:click="$dispatch('openModal', { component: 'admin.users.form-modal', arguments: { user: {{ $user->id }} } })"
                                                variant="ghost"
                                                size="sm"
                                            >
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                                </svg>
                                                <span class="sr-only">{{ __('common.edit') }}</span>
                                            </x-ui.button>
                                        @endcan

                                        @can('suspend', $user)
                                            <x-ui.button
                                                variant="ghost"
                                                size="sm"
                                                data-confirm="{{ $user->status->value === 'active' ? __('users.confirm_suspend') : __('users.confirm_activate') }}"
                                                x-on:click="uiConfirm($el.dataset.confirm, () => $wire.toggleStatus({{ $user->id }}), { danger: {{ $user->status->value === 'active' ? 'true' : 'false' }} })"
                                            >
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M18.36 6.64a9 9 0 1 1-12.73 0M12 3v9" />
                                                </svg>
                                                <span class="sr-only">{{ __('users.toggle_status') }}</span>
                                            </x-ui.button>
                                        @endcan

                                        @can('delete', $user)
                                            <x-ui.button
                                                variant="danger"
                                                size="sm"
                                                data-confirm="{{ __('users.confirm_delete') }}"
                                                x-on:click="uiConfirm($el.dataset.confirm, () => $wire.delete({{ $user->id }}), { danger: true })"
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
                    {{ $this->users->links() }}
                </div>
            @endif
        </div>
        </div>
    </x-ui.card>
</div>

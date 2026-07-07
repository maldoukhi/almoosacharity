@php
    $isEdit = $flow?->exists ?? false;

    $roleOptions = collect($this->roles)->mapWithKeys(fn ($role) => [$role->value => $role->label()]);
    $stagesCount = count($stages);
@endphp

<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">
                {{ $isEdit ? __('approval_flows.edit_title') : __('approval_flows.create_title') }}
            </h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                {{ $isEdit ? __('approval_flows.edit_subtitle') : __('approval_flows.create_subtitle') }}
            </p>
        </div>

        <x-ui.button href="{{ route('admin.settings.approval-flows.index') }}" variant="ghost">
            {{ __('common.back') }}
        </x-ui.button>
    </div>

    <form wire:submit="save" class="space-y-6">
        <x-ui.card>
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <x-ui.input :label="__('approval_flows.field_name')" name="name" wire:model="name" autofocus />

                <div class="flex items-end gap-6">
                    <label class="flex cursor-pointer items-center gap-3 select-none">
                        <span class="relative inline-block h-6 w-11 shrink-0">
                            <input type="checkbox" wire:model="is_active" class="peer sr-only" />
                            <span class="absolute inset-0 rounded-full bg-gray-200 transition-colors duration-200 ease-out peer-checked:bg-primary dark:bg-white/10"></span>
                            <span class="absolute start-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow transition-transform duration-200 ease-out peer-checked:translate-x-5 rtl:peer-checked:-translate-x-5"></span>
                        </span>
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('approval_flows.field_is_active') }}</span>
                    </label>

                    <label class="flex cursor-pointer items-center gap-3 select-none">
                        <span class="relative inline-block h-6 w-11 shrink-0">
                            <input type="checkbox" wire:model="is_default" class="peer sr-only" />
                            <span class="absolute inset-0 rounded-full bg-gray-200 transition-colors duration-200 ease-out peer-checked:bg-primary dark:bg-white/10"></span>
                            <span class="absolute start-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow transition-transform duration-200 ease-out peer-checked:translate-x-5 rtl:peer-checked:-translate-x-5"></span>
                        </span>
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('approval_flows.field_is_default') }}</span>
                    </label>
                </div>
            </div>
        </x-ui.card>

        <x-ui.card>
            <x-slot:header>
                <div class="flex items-center justify-between">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('approval_flows.stages_title') }}</h2>

                    <x-ui.button type="button" variant="ghost" size="sm" wire:click="addStage">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        {{ __('approval_flows.add_stage') }}
                    </x-ui.button>
                </div>
            </x-slot:header>

            @error('stages')
                <p class="mb-3 text-xs text-status-rejected">{{ $message }}</p>
            @enderror

            <div class="space-y-4">
                @forelse ($stages as $index => $stage)
                    <div
                        wire:key="approval-stage-{{ $index }}"
                        style="animation: fade-in-up 0.2s ease-out both"
                        class="rounded-(--radius-brand) border border-gray-200 p-4 dark:border-white/10"
                    >
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start">
                            <div class="flex shrink-0 items-center gap-2 sm:flex-col">
                                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-primary-50 text-sm font-semibold text-primary-700 dark:bg-primary-900/40 dark:text-primary-200">
                                    {{ $index + 1 }}
                                </span>

                                <div class="flex gap-1 sm:flex-col">
                                    <button
                                        type="button"
                                        wire:click="moveStage({{ $index }}, 'up')"
                                        @if ($index === 0) disabled @endif
                                        class="rounded-(--radius-brand) p-1.5 text-gray-400 transition duration-150 hover:bg-gray-100 hover:text-gray-600 disabled:cursor-not-allowed disabled:opacity-40 dark:hover:bg-white/10 dark:hover:text-gray-200"
                                    >
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" />
                                        </svg>
                                        <span class="sr-only">{{ __('approval_flows.move_up') }}</span>
                                    </button>

                                    <button
                                        type="button"
                                        wire:click="moveStage({{ $index }}, 'down')"
                                        @if ($index === $stagesCount - 1) disabled @endif
                                        class="rounded-(--radius-brand) p-1.5 text-gray-400 transition duration-150 hover:bg-gray-100 hover:text-gray-600 disabled:cursor-not-allowed disabled:opacity-40 dark:hover:bg-white/10 dark:hover:text-gray-200"
                                    >
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                        </svg>
                                        <span class="sr-only">{{ __('approval_flows.move_down') }}</span>
                                    </button>
                                </div>
                            </div>

                            <div class="grid flex-1 grid-cols-1 gap-4 sm:grid-cols-2">
                                <x-ui.input
                                    :label="__('approval_flows.field_stage_name')"
                                    name="stages.{{ $index }}.name"
                                    wire:model="stages.{{ $index }}.name"
                                />

                                <x-ui.select
                                    :label="__('approval_flows.field_stage_role')"
                                    name="stages.{{ $index }}.role"
                                    wire:model="stages.{{ $index }}.role"
                                    :placeholder="__('aids.select_placeholder')"
                                    :options="$roleOptions"
                                />

                                <div class="sm:col-span-2">
                                    <p class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('approval_flows.field_stage_actions') }}</p>

                                    <div class="flex flex-wrap gap-3">
                                        @foreach ($this->availableActions as $action)
                                            <label class="flex cursor-pointer items-center gap-2 rounded-(--radius-brand) border border-gray-200 px-3 py-2 text-sm text-gray-700 transition duration-150 ease-out hover:bg-gray-50 has-checked:border-primary-300 has-checked:bg-primary-50 dark:border-white/10 dark:text-gray-200 dark:hover:bg-white/5 dark:has-checked:border-primary-700 dark:has-checked:bg-primary-900/30">
                                                <input
                                                    type="checkbox"
                                                    wire:model="stages.{{ $index }}.allowed_actions"
                                                    value="{{ $action->value }}"
                                                    class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-2 focus:ring-primary-500/30 dark:border-white/20"
                                                />
                                                {{ $action->label() }}
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                            <div class="shrink-0">
                                <x-ui.button type="button" variant="danger" size="sm" wire:click="removeStage({{ $index }})">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                    </svg>
                                    <span class="sr-only">{{ __('common.delete') }}</span>
                                </x-ui.button>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('approval_flows.no_stages_yet') }}</p>
                @endforelse
            </div>
        </x-ui.card>

        <div class="flex items-center justify-end gap-3">
            <x-ui.button href="{{ route('admin.settings.approval-flows.index') }}" variant="ghost">
                {{ __('common.cancel') }}
            </x-ui.button>

            <x-ui.button type="submit" variant="primary" wire:target="save">
                {{ __('common.save') }}
            </x-ui.button>
        </div>
    </form>
</div>

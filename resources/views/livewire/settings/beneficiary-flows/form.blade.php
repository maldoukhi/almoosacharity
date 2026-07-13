@php
    $isEdit = $flow?->exists ?? false;

    // Key by the stored role name and translate the default ones, falling
    // back to the raw name for custom roles (same convention as the aid
    // approval-flow builder).
    $roleOptions = collect($this->roles)->mapWithKeys(fn ($role) => [
        $role->name => \Illuminate\Support\Facades\Lang::has('roles.names.'.$role->name)
            ? __('roles.names.'.$role->name)
            : $role->name,
    ]);

    $stagesCount = count($stages);

    // Literal Tailwind class strings per action, kept complete (not built by
    // concatenation) so the JIT scanner discovers them.
    $actionChipMap = [
        'approve' => [
            'chip' => 'has-checked:border-status-approved/40 has-checked:bg-status-approved/10 has-checked:text-status-approved dark:has-checked:border-status-approved dark:has-checked:bg-status-approved dark:has-checked:text-white',
            'icon' => 'm4.5 12.75 6 6 9-13.5',
            'mirror' => false,
        ],
        'reject' => [
            'chip' => 'has-checked:border-status-rejected/40 has-checked:bg-status-rejected/10 has-checked:text-status-rejected dark:has-checked:border-status-rejected dark:has-checked:bg-status-rejected dark:has-checked:text-white',
            'icon' => 'M6 18 18 6M6 6l12 12',
            'mirror' => false,
        ],
        'return' => [
            'chip' => 'has-checked:border-status-review/40 has-checked:bg-status-review/10 has-checked:text-status-review dark:has-checked:border-status-review dark:has-checked:bg-status-review dark:has-checked:text-white',
            'icon' => 'M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3',
            'mirror' => true,
        ],
    ];
@endphp

<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">
                {{ $isEdit ? __('beneficiaries.flow.edit_title') : __('beneficiaries.flow.create_title') }}
            </h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                {{ $isEdit ? __('beneficiaries.flow.edit_subtitle') : __('beneficiaries.flow.create_subtitle') }}
            </p>
        </div>

        <div class="flex items-center gap-2">
            <x-ui.help-link section="beneficiaries" />

            <x-ui.button href="{{ route('admin.settings.beneficiary-flows.index') }}" variant="ghost">
                {{ __('common.back') }}
            </x-ui.button>
        </div>
    </div>

    <form wire:submit="save" class="space-y-6">
        <x-ui.card>
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-3 sm:items-center">
                <div class="sm:col-span-2">
                    <x-ui.input :label="__('beneficiaries.flow.field_name')" name="name" wire:model="name" autofocus />
                </div>

                <div class="flex flex-col gap-3 sm:gap-4">
                    <x-ui.toggle wire:model="is_active" name="is_active" :label="__('beneficiaries.flow.field_is_active')" />
                    <x-ui.toggle wire:model="is_default" name="is_default" :label="__('beneficiaries.flow.field_is_default')" />
                </div>
            </div>
        </x-ui.card>

        <x-ui.card>
            <x-slot:header>
                <div class="flex items-center justify-between gap-2">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('beneficiaries.flow.stages_title') }}</h2>

                    <x-ui.badge color="primary">
                        {{ trans_choice('beneficiaries.flow.stages_count', $stagesCount, ['count' => $stagesCount]) }}
                    </x-ui.badge>
                </div>
            </x-slot:header>

            @error('stages')
                <p class="mb-4 text-xs text-status-rejected">{{ $message }}</p>
            @enderror

            <ol class="flex flex-col" aria-label="{{ __('beneficiaries.flow.stages_title') }}">
                {{-- Start node --}}
                <li class="relative flex items-stretch gap-4" style="animation: approval-node-in .3s ease-out both">
                    <div class="flex shrink-0 flex-col items-center">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-primary text-white shadow-(--shadow-card)">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM3 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 9.374 21c-2.331 0-4.512-.645-6.374-1.766Z" />
                            </svg>
                        </span>

                        <span class="relative mt-1 w-0.5 flex-1 rounded-full bg-gradient-to-b from-primary-300 via-primary-200 to-primary-100 dark:from-primary-700 dark:via-primary-800 dark:to-primary-900" aria-hidden="true">
                            <span class="absolute start-1/2 -ms-1 h-2 w-2 rounded-full bg-primary-500 shadow-[0_0_6px_0_var(--color-primary-400)] dark:bg-primary-400" style="animation: approval-flow-pulse 1.8s ease-in-out infinite"></span>
                        </span>

                        <svg class="my-0.5 h-3.5 w-3.5 shrink-0 text-primary-300 dark:text-primary-700" style="animation: approval-arrow-pulse 1.8s ease-in-out infinite" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                        </svg>
                    </div>

                    <div class="flex min-w-0 flex-1 items-center pb-8">
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('beneficiaries.flow.flow_start_label') }}</p>
                    </div>
                </li>

                @forelse ($stages as $index => $stage)
                    <li
                        wire:key="beneficiary-stage-{{ $index }}"
                        class="relative flex items-stretch gap-4"
                        style="animation: approval-node-in .3s ease-out both; animation-delay: {{ min($index + 1, 5) * 50 }}ms"
                    >
                        <div class="flex shrink-0 flex-col items-center">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full border-2 border-primary bg-white text-sm font-bold text-primary-700 shadow-(--shadow-card) dark:bg-primary-950 dark:text-primary-200">
                                {{ $index + 1 }}
                            </span>

                            <span class="relative mt-1 w-0.5 flex-1 rounded-full bg-gradient-to-b from-primary-300 via-primary-200 to-primary-100 dark:from-primary-700 dark:via-primary-800 dark:to-primary-900" aria-hidden="true">
                                <span class="absolute start-1/2 -ms-1 h-2 w-2 rounded-full bg-primary-500 shadow-[0_0_6px_0_var(--color-primary-400)] dark:bg-primary-400" style="animation: approval-flow-pulse 1.8s ease-in-out infinite; animation-delay: {{ $index * 200 }}ms"></span>
                            </span>

                            <svg class="my-0.5 h-3.5 w-3.5 shrink-0 text-primary-300 dark:text-primary-700" style="animation: approval-arrow-pulse 1.8s ease-in-out infinite; animation-delay: {{ $index * 100 }}ms" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                            </svg>
                        </div>

                        <div class="min-w-0 flex-1 pb-8">
                            <div class="rounded-(--radius-brand) border border-gray-200 bg-white p-4 shadow-(--shadow-card) transition-all duration-200 ease-out hover:border-primary-300 focus-within:border-primary-400 focus-within:ring-2 focus-within:ring-primary-500/20 dark:border-white/10 dark:bg-primary-950/30 dark:hover:border-primary-700">
                                <div class="mb-4 flex items-center justify-between gap-2">
                                    <p class="text-xs font-semibold tracking-wide text-gray-400 dark:text-gray-500">
                                        {{ __('beneficiaries.flow.stage_label', ['number' => $index + 1]) }}
                                    </p>

                                    <div class="flex items-center gap-0.5">
                                        <button type="button" wire:click="moveStage({{ $index }}, 'up')" @if ($index === 0) disabled @endif title="{{ __('beneficiaries.flow.move_up') }}" class="rounded-full p-1.5 text-gray-400 transition duration-150 ease-out hover:bg-gray-100 hover:text-primary-700 disabled:pointer-events-none disabled:opacity-0 dark:hover:bg-white/10 dark:hover:text-primary-200">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" /></svg>
                                            <span class="sr-only">{{ __('beneficiaries.flow.move_up') }}</span>
                                        </button>

                                        <button type="button" wire:click="moveStage({{ $index }}, 'down')" @if ($index === $stagesCount - 1) disabled @endif title="{{ __('beneficiaries.flow.move_down') }}" class="rounded-full p-1.5 text-gray-400 transition duration-150 ease-out hover:bg-gray-100 hover:text-primary-700 disabled:pointer-events-none disabled:opacity-0 dark:hover:bg-white/10 dark:hover:text-primary-200">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5 7.5" /></svg>
                                            <span class="sr-only">{{ __('beneficiaries.flow.move_down') }}</span>
                                        </button>

                                        <span class="mx-1 h-4 w-px bg-gray-200 dark:bg-white/10" aria-hidden="true"></span>

                                        <button type="button" wire:click="removeStage({{ $index }})" title="{{ __('common.delete') }}" class="rounded-full p-1.5 text-gray-400 transition duration-150 ease-out hover:bg-status-rejected/10 hover:text-status-rejected dark:hover:bg-status-rejected/20">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                                            <span class="sr-only">{{ __('common.delete') }}</span>
                                        </button>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <x-ui.input
                                        :label="__('beneficiaries.flow.field_stage_name')"
                                        name="stages.{{ $index }}.name"
                                        wire:model="stages.{{ $index }}.name"
                                    />

                                    <x-ui.select
                                        :label="__('beneficiaries.flow.field_stage_role')"
                                        name="stages.{{ $index }}.role"
                                        wire:model="stages.{{ $index }}.role"
                                        :placeholder="__('beneficiaries.flow.stage_role_none')"
                                        :options="$roleOptions"
                                        :hint="__('beneficiaries.flow.field_stage_role_hint')"
                                    />

                                    <div class="sm:col-span-2">
                                        <p class="mb-1 text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('beneficiaries.flow.field_stage_users') }}</p>
                                        <p class="mb-2 text-xs text-gray-500 dark:text-gray-400">{{ __('beneficiaries.flow.field_stage_users_hint') }}</p>

                                        <div class="max-h-40 space-y-1.5 overflow-y-auto rounded-(--radius-brand) border border-gray-200 p-3 dark:border-white/10">
                                            @forelse ($this->users as $user)
                                                <label wire:key="bstage-{{ $index }}-user-{{ $user->id }}" class="flex cursor-pointer items-center gap-2 text-sm text-gray-700 select-none dark:text-gray-200">
                                                    <input type="checkbox" wire:model="stages.{{ $index }}.assignee_user_ids" value="{{ $user->id }}" class="rounded border-gray-300 text-primary focus:ring-2 focus:ring-primary-500/30 dark:border-white/20 dark:bg-transparent" />
                                                    {{ $user->name }}
                                                </label>
                                            @empty
                                                <p class="text-xs text-gray-400 dark:text-gray-500">{{ __('beneficiaries.flow.no_users') }}</p>
                                            @endforelse
                                        </div>

                                        @error("stages.{$index}.role")
                                            <p class="mt-1.5 text-xs text-status-rejected">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div class="sm:col-span-2">
                                        <p class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('beneficiaries.flow.field_stage_actions') }}</p>

                                        <div class="flex flex-wrap gap-2">
                                            @foreach ($this->availableActions as $action)
                                                @php
                                                    $chip = $actionChipMap[$action->value] ?? ['chip' => '', 'icon' => null, 'mirror' => false];
                                                @endphp

                                                <label @class([
                                                    'inline-flex cursor-pointer items-center gap-1.5 rounded-full border px-3.5 py-1.5 text-sm font-medium transition-all duration-200 ease-out select-none',
                                                    'border-gray-200 bg-gray-50 text-gray-500 hover:border-gray-300 hover:bg-gray-100 dark:border-white/10 dark:bg-white/5 dark:text-gray-400 dark:hover:bg-white/10',
                                                    $chip['chip'],
                                                ])>
                                                    <input type="checkbox" wire:model="stages.{{ $index }}.allowed_actions" value="{{ $action->value }}" class="sr-only" />

                                                    @if ($chip['icon'])
                                                        <svg class="h-3.5 w-3.5 shrink-0 {{ $chip['mirror'] ? 'rtl:-scale-x-100' : '' }}" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $chip['icon'] }}" />
                                                        </svg>
                                                    @endif

                                                    {{ $action->label() }}
                                                </label>
                                            @endforeach
                                        </div>

                                        @error("stages.{$index}.allowed_actions")
                                            <p class="mt-1.5 text-xs text-status-rejected">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </li>
                @empty
                    <li class="relative flex items-stretch gap-4 pb-8">
                        <div class="flex shrink-0 flex-col items-center">
                            <span class="h-11 w-11" aria-hidden="true"></span>
                        </div>
                        <p class="flex flex-1 items-center text-sm text-gray-500 dark:text-gray-400">{{ __('beneficiaries.flow.no_stages_yet') }}</p>
                    </li>
                @endforelse

                {{-- Add-stage node --}}
                <li class="relative flex items-stretch gap-4" style="animation: approval-node-in .3s ease-out both; animation-delay: {{ min($stagesCount + 1, 5) * 50 }}ms">
                    <div class="flex shrink-0 flex-col items-center">
                        <button type="button" wire:click="addStage" class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full border-2 border-dashed border-gray-300 text-gray-400 transition-all duration-200 ease-out hover:border-primary hover:bg-primary-50 hover:text-primary-700 dark:border-white/20 dark:text-gray-500 dark:hover:border-primary-400 dark:hover:bg-primary-900/30 dark:hover:text-primary-200">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                            <span class="sr-only">{{ __('beneficiaries.flow.add_stage') }}</span>
                        </button>

                        <span class="relative mt-1 w-0.5 flex-1 rounded-full bg-gradient-to-b from-primary-300 via-primary-200 to-primary-100 dark:from-primary-700 dark:via-primary-800 dark:to-primary-900" aria-hidden="true">
                            <span class="absolute start-1/2 -ms-1 h-2 w-2 rounded-full bg-primary-500 shadow-[0_0_6px_0_var(--color-primary-400)] dark:bg-primary-400" style="animation: approval-flow-pulse 1.8s ease-in-out infinite"></span>
                        </span>

                        <svg class="my-0.5 h-3.5 w-3.5 shrink-0 text-primary-300 dark:text-primary-700" style="animation: approval-arrow-pulse 1.8s ease-in-out infinite" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                        </svg>
                    </div>

                    <div class="flex min-w-0 flex-1 items-center pb-8">
                        <button type="button" wire:click="addStage" class="text-sm font-semibold text-gray-500 transition-colors duration-150 ease-out hover:text-primary-700 dark:text-gray-400 dark:hover:text-primary-200">
                            {{ __('beneficiaries.flow.add_stage') }}
                        </button>
                    </div>
                </li>

                {{-- End node --}}
                <li class="relative flex items-stretch gap-4" style="animation: approval-node-in .3s ease-out both; animation-delay: {{ min($stagesCount + 2, 5) * 50 }}ms">
                    <div class="flex shrink-0 flex-col items-center">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-secondary text-white shadow-(--shadow-card)">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                        </span>
                    </div>

                    <div class="flex min-w-0 flex-1 flex-col justify-center gap-1.5">
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('beneficiaries.flow.flow_end_label') }}</p>
                        <x-ui.badge color="approved" class="w-fit">{{ __('beneficiaries.status.active') }}</x-ui.badge>
                    </div>
                </li>
            </ol>
        </x-ui.card>

        <div class="flex items-center justify-end gap-3">
            <x-ui.button href="{{ route('admin.settings.beneficiary-flows.index') }}" variant="ghost">
                {{ __('common.cancel') }}
            </x-ui.button>

            <x-ui.button type="submit" variant="primary" wire:target="save">
                {{ __('common.save') }}
            </x-ui.button>
        </div>
    </form>
</div>

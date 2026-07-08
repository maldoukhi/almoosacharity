@php
    $isEdit = $flow?->exists ?? false;

    // $this->roles returns Spatie Role models (incl. custom roles), not
    // RoleName enums: key by the stored role name and translate the default
    // ones, falling back to the raw name for custom roles.
    $roleOptions = collect($this->roles)->mapWithKeys(fn ($role) => [
        $role->name => \Illuminate\Support\Facades\Lang::has('roles.names.'.$role->name)
            ? __('roles.names.'.$role->name)
            : $role->name,
    ]);

    $stagesCount = count($stages);

    // Literal Tailwind class strings per approval action, kept as complete
    // literal values (not built by string concatenation) so the JIT content
    // scanner can discover them — same convention as x-ui.badge/x-ui.timeline.
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
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-3 sm:items-center">
                <div class="sm:col-span-2">
                    <x-ui.input :label="__('approval_flows.field_name')" name="name" wire:model="name" autofocus />
                </div>

                <div class="flex flex-col gap-3 sm:gap-4">
                    <x-ui.toggle wire:model="is_active" name="is_active" :label="__('approval_flows.field_is_active')" />
                    <x-ui.toggle wire:model="is_default" name="is_default" :label="__('approval_flows.field_is_default')" />
                </div>
            </div>
        </x-ui.card>

        <x-ui.card>
            <x-slot:header>
                <div class="flex items-center justify-between gap-2">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('approval_flows.stages_title') }}</h2>

                    <x-ui.badge color="primary">
                        {{ trans_choice('approval_flows.stages_count', $stagesCount, ['count' => $stagesCount]) }}
                    </x-ui.badge>
                </div>
            </x-slot:header>

            @error('stages')
                <p class="mb-4 text-xs text-status-rejected">{{ $message }}</p>
            @enderror

            {{-- Vertical flow spine: start node -> each stage -> add-stage node -> end node --}}
            <ol class="flex flex-col" aria-label="{{ __('approval_flows.stages_title') }}">
                {{-- Start node --}}
                <li class="relative flex items-stretch gap-4" style="animation: approval-node-in .3s ease-out both">
                    <div class="flex shrink-0 flex-col items-center">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-primary text-white shadow-(--shadow-card)">
                            <svg class="h-5 w-5 rtl:-scale-x-100" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                            </svg>
                        </span>

                        <span class="relative mt-1 w-0.5 flex-1 rounded-full bg-gradient-to-b from-primary-300 via-primary-200 to-primary-100 dark:from-primary-700 dark:via-primary-800 dark:to-primary-900" aria-hidden="true">
                            <span
                                class="absolute start-1/2 -ms-1 h-2 w-2 rounded-full bg-primary-500 shadow-[0_0_6px_0_var(--color-primary-400)] dark:bg-primary-400"
                                style="animation: approval-flow-pulse 1.8s ease-in-out infinite"
                            ></span>
                        </span>

                        <svg
                            class="my-0.5 h-3.5 w-3.5 shrink-0 text-primary-300 dark:text-primary-700"
                            style="animation: approval-arrow-pulse 1.8s ease-in-out infinite"
                            fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                        </svg>
                    </div>

                    <div class="flex min-w-0 flex-1 items-center pb-8">
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('approval_flows.flow_start_label') }}</p>
                    </div>
                </li>

                @forelse ($stages as $index => $stage)
                    <li
                        wire:key="approval-stage-{{ $index }}"
                        x-data
                        x-transition:leave="transition-all duration-200 ease-in"
                        x-transition:leave-start="opacity-100 scale-100"
                        x-transition:leave-end="opacity-0 scale-90"
                        class="relative flex items-stretch gap-4"
                        style="animation: approval-node-in .3s ease-out both; animation-delay: {{ min($index + 1, 5) * 50 }}ms"
                    >
                        <div class="flex shrink-0 flex-col items-center">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full border-2 border-primary bg-white text-sm font-bold text-primary-700 shadow-(--shadow-card) dark:bg-primary-950 dark:text-primary-200">
                                {{ $index + 1 }}
                            </span>

                            <span class="relative mt-1 w-0.5 flex-1 rounded-full bg-gradient-to-b from-primary-300 via-primary-200 to-primary-100 dark:from-primary-700 dark:via-primary-800 dark:to-primary-900" aria-hidden="true">
                                <span
                                    class="absolute start-1/2 -ms-1 h-2 w-2 rounded-full bg-primary-500 shadow-[0_0_6px_0_var(--color-primary-400)] dark:bg-primary-400"
                                    style="animation: approval-flow-pulse 1.8s ease-in-out infinite; animation-delay: {{ $index * 200 }}ms"
                                ></span>
                            </span>

                            <svg
                                class="my-0.5 h-3.5 w-3.5 shrink-0 text-primary-300 dark:text-primary-700"
                                style="animation: approval-arrow-pulse 1.8s ease-in-out infinite; animation-delay: {{ $index * 100 }}ms"
                                fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                            </svg>
                        </div>

                        <div class="min-w-0 flex-1 pb-8">
                            <div class="rounded-(--radius-brand) border border-gray-200 bg-white p-4 shadow-(--shadow-card) transition-all duration-200 ease-out hover:border-primary-300 hover:shadow-md focus-within:border-primary-400 focus-within:ring-2 focus-within:ring-primary-500/20 dark:border-white/10 dark:bg-primary-950/30 dark:hover:border-primary-700">
                                <div class="mb-4 flex items-center justify-between gap-2">
                                    <p class="text-xs font-semibold tracking-wide text-gray-400 dark:text-gray-500">
                                        {{ __('approval_flows.stage_label', ['number' => $index + 1]) }}
                                    </p>

                                    <div class="flex items-center gap-0.5">
                                        <button
                                            type="button"
                                            wire:click="moveStage({{ $index }}, 'up')"
                                            @if ($index === 0) disabled @endif
                                            title="{{ __('approval_flows.move_up') }}"
                                            class="rounded-full p-1.5 text-gray-400 transition duration-150 ease-out hover:bg-gray-100 hover:text-primary-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-500 disabled:pointer-events-none disabled:opacity-0 dark:hover:bg-white/10 dark:hover:text-primary-200"
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
                                            title="{{ __('approval_flows.move_down') }}"
                                            class="rounded-full p-1.5 text-gray-400 transition duration-150 ease-out hover:bg-gray-100 hover:text-primary-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-500 disabled:pointer-events-none disabled:opacity-0 dark:hover:bg-white/10 dark:hover:text-primary-200"
                                        >
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                            </svg>
                                            <span class="sr-only">{{ __('approval_flows.move_down') }}</span>
                                        </button>

                                        <span class="mx-1 h-4 w-px bg-gray-200 dark:bg-white/10" aria-hidden="true"></span>

                                        <button
                                            type="button"
                                            wire:click="removeStage({{ $index }})"
                                            title="{{ __('common.delete') }}"
                                            class="rounded-full p-1.5 text-gray-400 transition duration-150 ease-out hover:bg-status-rejected/10 hover:text-status-rejected focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-status-rejected dark:hover:bg-status-rejected/20"
                                        >
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                            </svg>
                                            <span class="sr-only">{{ __('common.delete') }}</span>
                                        </button>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
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

                                        <div class="flex flex-wrap gap-2">
                                            @foreach ($this->availableActions as $action)
                                                @php
                                                    $chip = $actionChipMap[$action->value] ?? ['chip' => '', 'icon' => null, 'mirror' => false];
                                                @endphp

                                                <label
                                                    @class([
                                                        'inline-flex cursor-pointer items-center gap-1.5 rounded-full border px-3.5 py-1.5 text-sm font-medium transition-all duration-200 ease-out select-none',
                                                        'border-gray-200 bg-gray-50 text-gray-500 hover:border-gray-300 hover:bg-gray-100 dark:border-white/10 dark:bg-white/5 dark:text-gray-400 dark:hover:bg-white/10',
                                                        'has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-primary-500/40 has-[:focus-visible]:ring-offset-1 dark:has-[:focus-visible]:ring-offset-primary-950',
                                                        $chip['chip'],
                                                    ])
                                                >
                                                    <input
                                                        type="checkbox"
                                                        wire:model="stages.{{ $index }}.allowed_actions"
                                                        value="{{ $action->value }}"
                                                        class="sr-only"
                                                    />

                                                    @if ($chip['icon'])
                                                        <svg
                                                            class="h-3.5 w-3.5 shrink-0 {{ $chip['mirror'] ? 'rtl:-scale-x-100' : '' }}"
                                                            fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true"
                                                        >
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $chip['icon'] }}" />
                                                        </svg>
                                                    @endif

                                                    {{ $action->label() }}
                                                </label>
                                            @endforeach
                                        </div>
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

                        <p class="flex flex-1 items-center text-sm text-gray-500 dark:text-gray-400">{{ __('approval_flows.no_stages_yet') }}</p>
                    </li>
                @endforelse

                {{-- Add-stage node: sits on the spine itself, right before the end node --}}
                <li class="relative flex items-stretch gap-4" style="animation: approval-node-in .3s ease-out both; animation-delay: {{ min($stagesCount + 1, 5) * 50 }}ms">
                    <div class="flex shrink-0 flex-col items-center">
                        <button
                            type="button"
                            wire:click="addStage"
                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full border-2 border-dashed border-gray-300 text-gray-400 transition-all duration-200 ease-out hover:border-primary hover:bg-primary-50 hover:text-primary-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-500 dark:border-white/20 dark:text-gray-500 dark:hover:border-primary-400 dark:hover:bg-primary-900/30 dark:hover:text-primary-200"
                        >
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            <span class="sr-only">{{ __('approval_flows.add_stage') }}</span>
                        </button>

                        <span class="relative mt-1 w-0.5 flex-1 rounded-full bg-gradient-to-b from-primary-300 via-primary-200 to-primary-100 dark:from-primary-700 dark:via-primary-800 dark:to-primary-900" aria-hidden="true">
                            <span
                                class="absolute start-1/2 -ms-1 h-2 w-2 rounded-full bg-primary-500 shadow-[0_0_6px_0_var(--color-primary-400)] dark:bg-primary-400"
                                style="animation: approval-flow-pulse 1.8s ease-in-out infinite"
                            ></span>
                        </span>

                        <svg
                            class="my-0.5 h-3.5 w-3.5 shrink-0 text-primary-300 dark:text-primary-700"
                            style="animation: approval-arrow-pulse 1.8s ease-in-out infinite"
                            fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                        </svg>
                    </div>

                    <div class="flex min-w-0 flex-1 items-center pb-8">
                        <button
                            type="button"
                            wire:click="addStage"
                            class="text-sm font-semibold text-gray-500 transition-colors duration-150 ease-out hover:text-primary-700 dark:text-gray-400 dark:hover:text-primary-200"
                        >
                            {{ __('approval_flows.add_stage') }}
                        </button>
                    </div>
                </li>

                {{-- End node --}}
                <li class="relative flex items-stretch gap-4" style="animation: approval-node-in .3s ease-out both; animation-delay: {{ min($stagesCount + 2, 5) * 50 }}ms">
                    <div class="flex shrink-0 flex-col items-center">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-secondary text-white shadow-(--shadow-card)">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                            </svg>
                        </span>
                    </div>

                    <div class="flex min-w-0 flex-1 flex-col justify-center gap-1.5">
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('approval_flows.flow_end_label') }}</p>
                        <x-ui.badge color="approved" class="w-fit">{{ __('aids.status.approved') }}</x-ui.badge>
                    </div>
                </li>
            </ol>
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

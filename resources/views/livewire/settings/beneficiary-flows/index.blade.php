<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ __('beneficiaries.flow.index_title') }}</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('beneficiaries.flow.index_subtitle') }}</p>
        </div>

        @can('manage', \App\Models\BeneficiaryFlow::class)
            <x-ui.button href="{{ route('admin.settings.beneficiary-flows.create') }}" variant="primary">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                {{ __('beneficiaries.flow.create_button') }}
            </x-ui.button>
        @endcan
    </div>

    @if ($this->flows->isEmpty())
        <x-ui.empty-state :title="__('beneficiaries.flow.empty_title')" :description="__('beneficiaries.flow.empty_description')">
            <x-slot:icon>
                <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
            </x-slot:icon>
        </x-ui.empty-state>
    @else
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($this->flows as $flow)
                <x-ui.card wire:key="beneficiary-flow-{{ $flow->id }}">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="truncate font-semibold text-gray-900 dark:text-white">{{ $flow->name }}</p>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                {{ trans_choice('beneficiaries.flow.stages_count', $flow->stages->count(), ['count' => $flow->stages->count()]) }}
                            </p>
                        </div>

                        <div class="flex shrink-0 flex-wrap justify-end gap-1.5">
                            @if ($flow->is_default)
                                <x-ui.badge color="accent">{{ __('beneficiaries.flow.default_badge') }}</x-ui.badge>
                            @endif

                            <x-ui.badge :color="$flow->is_active ? 'approved' : 'draft'">
                                {{ $flow->is_active ? __('common.active') : __('common.inactive') }}
                            </x-ui.badge>
                        </div>
                    </div>

                    <div class="mt-4 flex flex-wrap items-center gap-2 border-t border-gray-100 pt-4 dark:border-white/10">
                        @can('manage', $flow)
                            @if (! $flow->is_default)
                                <x-ui.button variant="ghost" size="sm" wire:click="setDefault({{ $flow->id }})">
                                    {{ __('beneficiaries.flow.set_default_button') }}
                                </x-ui.button>
                            @endif

                            <x-ui.button href="{{ route('admin.settings.beneficiary-flows.edit', $flow) }}" variant="ghost" size="sm">
                                {{ __('common.edit') }}
                            </x-ui.button>

                            <x-ui.button
                                variant="danger"
                                size="sm"
                                data-confirm="{{ __('beneficiaries.flow.confirm_delete') }}"
                                x-on:click="uiConfirm($el.dataset.confirm, () => $wire.delete({{ $flow->id }}), { danger: true })"
                            >
                                {{ __('common.delete') }}
                            </x-ui.button>
                        @endcan
                    </div>
                </x-ui.card>
            @endforeach
        </div>
    @endif
</div>

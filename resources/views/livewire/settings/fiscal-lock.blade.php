<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ __('reports.fiscal.title') }}</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('reports.fiscal.subtitle') }}</p>
        </div>

        <x-ui.help-link section="admin" />
    </div>

    <x-ui.card>
        <div class="space-y-5">
            <div class="rounded-(--radius-brand) border border-gray-100 bg-gray-50 px-4 py-3 text-sm dark:border-white/10 dark:bg-white/5">
                @if ($this->currentLock !== null)
                    <span class="text-gray-700 dark:text-gray-200">
                        {{ __('reports.fiscal.current_lock', ['year' => $this->currentLock]) }}
                    </span>
                @else
                    <span class="text-gray-500 dark:text-gray-400">{{ __('reports.fiscal.no_lock') }}</span>
                @endif
            </div>

            <p class="text-sm text-gray-600 dark:text-gray-300">{{ __('reports.fiscal.explanation') }}</p>

            <form wire:submit="save" class="space-y-4">
                <div class="max-w-xs">
                    <x-ui.input
                        :label="__('reports.fiscal.year_label')"
                        name="year"
                        type="number"
                        min="2000"
                        max="{{ now()->year }}"
                        step="1"
                        wire:model="year"
                        :hint="__('reports.fiscal.year_hint')"
                    />
                </div>

                <div class="flex items-center gap-2">
                    <x-ui.button type="submit" variant="primary">
                        {{ __('reports.fiscal.save') }}
                    </x-ui.button>

                    @if ($this->currentLock !== null)
                        <x-ui.button type="button" variant="ghost" wire:click="clearLock">
                            {{ __('reports.fiscal.clear') }}
                        </x-ui.button>
                    @endif
                </div>
            </form>
        </div>
    </x-ui.card>
</div>

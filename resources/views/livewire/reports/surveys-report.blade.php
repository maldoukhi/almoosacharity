<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-2">
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ __('reports.surveys.title') }}</h1>
            <x-ui.badge color="accent">{{ __('reports.surveys.coming_soon_badge') }}</x-ui.badge>
        </div>

        @can('reports.export')
            <div class="flex items-center gap-2">
                <x-ui.button variant="secondary" size="sm" disabled title="{{ __('reports.surveys.stub_notice') }}">
                    {{ __('reports.actions.export_excel') }}
                </x-ui.button>
                <x-ui.button variant="ghost" size="sm" disabled title="{{ __('reports.surveys.stub_notice') }}">
                    {{ __('reports.actions.export_pdf') }}
                </x-ui.button>
            </div>
        @endcan
    </div>

    <x-ui.empty-state :title="__('reports.surveys.stub_notice')" :description="__('reports.surveys.subtitle')">
        <x-slot:icon>
            <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
        </x-slot:icon>
    </x-ui.empty-state>
</div>

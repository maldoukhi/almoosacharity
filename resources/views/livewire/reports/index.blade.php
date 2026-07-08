<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ __('reports.index.title') }}</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('reports.index.subtitle') }}</p>
    </div>

    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <x-ui.card>
            <div class="flex h-full flex-col justify-between gap-4">
                <div>
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('reports.index.card_aids_title') }}</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('reports.index.card_aids_description') }}</p>
                </div>
                <x-ui.button href="{{ route('reports.aids') }}" variant="primary" size="sm">
                    {{ __('reports.index.open') }}
                </x-ui.button>
            </div>
        </x-ui.card>

        <x-ui.card>
            <div class="flex h-full flex-col justify-between gap-4">
                <div>
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('reports.index.card_beneficiaries_title') }}</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('reports.index.card_beneficiaries_description') }}</p>
                </div>
                <x-ui.button href="{{ route('reports.beneficiaries') }}" variant="primary" size="sm">
                    {{ __('reports.index.open') }}
                </x-ui.button>
            </div>
        </x-ui.card>

        <x-ui.card>
            <div class="flex h-full flex-col justify-between gap-4">
                <div>
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('reports.index.card_financial_title') }}</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('reports.index.card_financial_description') }}</p>
                </div>
                <x-ui.button href="{{ route('reports.financial') }}" variant="primary" size="sm">
                    {{ __('reports.index.open') }}
                </x-ui.button>
            </div>
        </x-ui.card>

        <x-ui.card>
            <div class="flex h-full flex-col justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('reports.index.card_surveys_title') }}</h2>
                        <x-ui.badge color="accent">{{ __('reports.index.coming_soon') }}</x-ui.badge>
                    </div>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('reports.index.card_surveys_description') }}</p>
                </div>
                <x-ui.button href="{{ route('reports.surveys') }}" variant="ghost" size="sm">
                    {{ __('reports.index.open') }}
                </x-ui.button>
            </div>
        </x-ui.card>

        <x-ui.card>
            <div class="flex h-full flex-col justify-between gap-4">
                <div>
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('reports.index.card_messages_title') }}</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('reports.index.card_messages_description') }}</p>
                </div>
                <x-ui.button href="{{ route('reports.messages') }}" variant="primary" size="sm">
                    {{ __('reports.index.open') }}
                </x-ui.button>
            </div>
        </x-ui.card>
    </div>
</div>

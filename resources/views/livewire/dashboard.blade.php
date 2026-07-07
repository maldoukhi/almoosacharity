<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">
            {{ __('nav.welcome', ['name' => auth()->user()->name]) }}
        </h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('nav.dashboard_subtitle') }}</p>
    </div>

    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
        <x-ui.stat-card :label="__('users.stat_label')" :value="$this->usersCount">
            <x-slot:icon>
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                </svg>
            </x-slot:icon>
        </x-ui.stat-card>

        <x-ui.stat-card :label="__('ui.stat_beneficiaries')" :value="0">
            <x-slot:icon>
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 9.75 12 6l3 3.75M9 14.25 12 18l3-3.75" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 4.5h15v15h-15z" />
                </svg>
            </x-slot:icon>
        </x-ui.stat-card>

        <x-ui.stat-card :label="__('ui.stat_aids')" :value="0">
            <x-slot:icon>
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-6-6h12" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75h16.5v16.5H3.75z" />
                </svg>
            </x-slot:icon>
        </x-ui.stat-card>
    </div>
</div>

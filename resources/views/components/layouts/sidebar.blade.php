{{-- Backdrop for the mobile drawer --}}
<div
    x-show="sidebarOpen"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    x-on:click="sidebarOpen = false"
    class="fixed inset-0 z-30 bg-primary-950/50 lg:hidden"
    style="display: none"
    aria-hidden="true"
></div>

<aside
    x-cloak
    :class="[
        sidebarOpen ? '!translate-x-0' : '',
        sidebarCollapsed ? 'lg:w-20' : 'lg:w-64',
    ]"
    class="fixed inset-y-0 start-0 z-40 flex w-64 -translate-x-full flex-col border-e border-gray-100 bg-white transition-all duration-200 ease-out rtl:translate-x-full ltr:-translate-x-full lg:static lg:translate-x-0 dark:border-white/10 dark:bg-primary-950"
>
    <div class="flex h-16 shrink-0 items-center gap-3 border-b border-gray-100 px-4 dark:border-white/10">
        <img src="{{ asset('images/brand/logo.svg') }}" alt="{{ config('app.name') }}" class="h-8 w-auto shrink-0">
        <span
            x-show="! sidebarCollapsed"
            x-transition.opacity.duration.150ms
            class="truncate text-sm font-semibold text-primary-800 dark:text-white"
        >
            {{ config('app.name') }}
        </span>
    </div>

    <nav class="flex-1 space-y-1 overflow-y-auto overflow-x-hidden px-3 py-4">
        <a
            href="{{ route('dashboard') }}"
            wire:navigate
            title="{{ __('nav.dashboard') }}"
            style="animation: fade-in-up 0.3s ease-out both; animation-delay: 0ms"
            @class([
                'group flex items-center gap-3 rounded-(--radius-brand) border-s-4 px-3 py-2.5 text-sm font-medium transition duration-150 ease-out',
                'border-primary bg-primary-50 text-primary-800 dark:bg-primary-900/40 dark:text-white' => request()->routeIs('dashboard'),
                'border-transparent text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-white/5 dark:hover:text-white' => ! request()->routeIs('dashboard'),
            ])
        >
            <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
            </svg>
            <span x-show="! sidebarCollapsed" x-transition.opacity.duration.150ms class="truncate">
                {{ __('nav.dashboard') }}
            </span>
        </a>

        @can('users.view')
            <a
                href="{{ route('admin.users.index') }}"
                wire:navigate
                title="{{ __('nav.users') }}"
                style="animation: fade-in-up 0.3s ease-out both; animation-delay: 60ms"
                @class([
                    'group flex items-center gap-3 rounded-(--radius-brand) border-s-4 px-3 py-2.5 text-sm font-medium transition duration-150 ease-out',
                    'border-primary bg-primary-50 text-primary-800 dark:bg-primary-900/40 dark:text-white' => request()->routeIs('admin.users.*'),
                    'border-transparent text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-white/5 dark:hover:text-white' => ! request()->routeIs('admin.users.*'),
                ])
            >
                <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                </svg>
                <span x-show="! sidebarCollapsed" x-transition.opacity.duration.150ms class="truncate">
                    {{ __('nav.users') }}
                </span>
            </a>
        @endcan

        @can('roles.view')
            <a
                href="{{ route('admin.roles.index') }}"
                wire:navigate
                title="{{ __('nav.roles') }}"
                style="animation: fade-in-up 0.3s ease-out both; animation-delay: 120ms"
                @class([
                    'group flex items-center gap-3 rounded-(--radius-brand) border-s-4 px-3 py-2.5 text-sm font-medium transition duration-150 ease-out',
                    'border-primary bg-primary-50 text-primary-800 dark:bg-primary-900/40 dark:text-white' => request()->routeIs('admin.roles.*'),
                    'border-transparent text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-white/5 dark:hover:text-white' => ! request()->routeIs('admin.roles.*'),
                ])
            >
                <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
                <span x-show="! sidebarCollapsed" x-transition.opacity.duration.150ms class="truncate">
                    {{ __('nav.roles') }}
                </span>
            </a>
        @endcan
    </nav>

    <div class="shrink-0 border-t border-gray-100 p-3 dark:border-white/10">
        <button
            type="button"
            x-on:click="sidebarCollapsed = ! sidebarCollapsed"
            title="{{ __('common.toggle_sidebar') }}"
            class="hidden w-full items-center justify-center rounded-(--radius-brand) p-2 text-gray-400 transition duration-150 hover:bg-gray-50 hover:text-gray-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-primary-500 lg:flex dark:hover:bg-white/5 dark:hover:text-gray-200"
        >
            <svg
                class="h-5 w-5 shrink-0 transition-transform duration-200"
                :class="sidebarCollapsed ? 'rotate-180' : ''"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="1.5"
                stroke="currentColor"
                aria-hidden="true"
            >
                <path stroke-linecap="round" stroke-linejoin="round" d="M18.75 19.5l-7.5-7.5 7.5-7.5m-6 15L5.25 12l7.5-7.5" />
            </svg>
        </button>
    </div>
</aside>

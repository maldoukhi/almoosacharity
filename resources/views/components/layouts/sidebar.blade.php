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
        sidebarOpen ? 'translate-x-0!' : '',
        sidebarCollapsed ? 'lg:w-20' : 'lg:w-64',
    ]"
    class="fixed inset-y-0 start-0 z-40 flex w-64 flex-col border-e border-gray-100 bg-white transition-all duration-200 ease-out max-lg:rtl:translate-x-full max-lg:ltr:-translate-x-full lg:static dark:border-white/10 dark:bg-primary-950"
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

        @can('beneficiaries.view')
            <a
                href="{{ route('admin.beneficiaries.index') }}"
                wire:navigate
                title="{{ __('nav.beneficiaries') }}"
                style="animation: fade-in-up 0.3s ease-out both; animation-delay: 60ms"
                @class([
                    'group flex items-center gap-3 rounded-(--radius-brand) border-s-4 px-3 py-2.5 text-sm font-medium transition duration-150 ease-out',
                    'border-primary bg-primary-50 text-primary-800 dark:bg-primary-900/40 dark:text-white' => request()->routeIs('admin.beneficiaries.*'),
                    'border-transparent text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-white/5 dark:hover:text-white' => ! request()->routeIs('admin.beneficiaries.*'),
                ])
            >
                <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                </svg>
                <span x-show="! sidebarCollapsed" x-transition.opacity.duration.150ms class="truncate">
                    {{ __('nav.beneficiaries') }}
                </span>
            </a>
        @endcan

        @can('aids.view')
            <a
                href="{{ route('aids.index') }}"
                wire:navigate
                title="{{ __('nav.aids') }}"
                style="animation: fade-in-up 0.3s ease-out both; animation-delay: 90ms"
                @class([
                    'group flex items-center gap-3 rounded-(--radius-brand) border-s-4 px-3 py-2.5 text-sm font-medium transition duration-150 ease-out',
                    'border-primary bg-primary-50 text-primary-800 dark:bg-primary-900/40 dark:text-white' => request()->routeIs('aids.*'),
                    'border-transparent text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-white/5 dark:hover:text-white' => ! request()->routeIs('aids.*'),
                ])
            >
                <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5m6 4.125 2.25 2.25m0 0 2.25-2.25m-2.25 2.25V6.75m-8.25.75h16.5m-9-3H12a2.25 2.25 0 0 0-2.25 2.25v.75h4.5v-.75A2.25 2.25 0 0 0 12 3.75Z" />
                </svg>
                <span x-show="! sidebarCollapsed" x-transition.opacity.duration.150ms class="truncate">
                    {{ __('nav.aids') }}
                </span>
            </a>
        @endcan

        @can('approvals.view')
            <a
                href="{{ route('approvals.inbox') }}"
                wire:navigate
                title="{{ __('nav.approvals_inbox') }}"
                style="animation: fade-in-up 0.3s ease-out both; animation-delay: 100ms"
                @class([
                    'group flex items-center gap-3 rounded-(--radius-brand) border-s-4 px-3 py-2.5 text-sm font-medium transition duration-150 ease-out',
                    'border-primary bg-primary-50 text-primary-800 dark:bg-primary-900/40 dark:text-white' => request()->routeIs('approvals.*'),
                    'border-transparent text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-white/5 dark:hover:text-white' => ! request()->routeIs('approvals.*'),
                ])
            >
                <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
                <span x-show="! sidebarCollapsed" x-transition.opacity.duration.150ms class="truncate">
                    {{ __('nav.approvals_inbox') }}
                </span>
            </a>
        @endcan

        @can('users.view')
            <a
                href="{{ route('admin.users.index') }}"
                wire:navigate
                title="{{ __('nav.users') }}"
                style="animation: fade-in-up 0.3s ease-out both; animation-delay: 120ms"
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
                style="animation: fade-in-up 0.3s ease-out both; animation-delay: 180ms"
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

        @can('reports.view')
            <a
                href="{{ route('reports.index') }}"
                wire:navigate
                title="{{ __('nav.reports') }}"
                style="animation: fade-in-up 0.3s ease-out both; animation-delay: 205ms"
                @class([
                    'group flex items-center gap-3 rounded-(--radius-brand) border-s-4 px-3 py-2.5 text-sm font-medium transition duration-150 ease-out',
                    'border-primary bg-primary-50 text-primary-800 dark:bg-primary-900/40 dark:text-white' => request()->routeIs('reports.*'),
                    'border-transparent text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-white/5 dark:hover:text-white' => ! request()->routeIs('reports.*'),
                ])
            >
                <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                </svg>
                <span x-show="! sidebarCollapsed" x-transition.opacity.duration.150ms class="truncate">
                    {{ __('nav.reports') }}
                </span>
            </a>
        @endcan

        @can('surveys.view')
            <a
                href="{{ route('admin.surveys.index') }}"
                wire:navigate
                title="{{ __('nav.surveys') }}"
                style="animation: fade-in-up 0.3s ease-out both; animation-delay: 210ms"
                @class([
                    'group flex items-center gap-3 rounded-(--radius-brand) border-s-4 px-3 py-2.5 text-sm font-medium transition duration-150 ease-out',
                    'border-primary bg-primary-50 text-primary-800 dark:bg-primary-900/40 dark:text-white' => request()->routeIs('admin.surveys.*'),
                    'border-transparent text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-white/5 dark:hover:text-white' => ! request()->routeIs('admin.surveys.*'),
                ])
            >
                <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" />
                </svg>
                <span x-show="! sidebarCollapsed" x-transition.opacity.duration.150ms class="truncate">
                    {{ __('nav.surveys') }}
                </span>
            </a>
        @endcan

        @can('settings.view')
            <p
                x-show="! sidebarCollapsed"
                x-transition.opacity.duration.150ms
                class="px-3 pt-4 pb-1 text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500"
            >
                {{ __('nav.settings_group') }}
            </p>

            <a
                href="{{ route('admin.settings.categories.index') }}"
                wire:navigate
                title="{{ __('nav.categories') }}"
                style="animation: fade-in-up 0.3s ease-out both; animation-delay: 240ms"
                @class([
                    'group flex items-center gap-3 rounded-(--radius-brand) border-s-4 px-3 py-2.5 text-sm font-medium transition duration-150 ease-out',
                    'border-primary bg-primary-50 text-primary-800 dark:bg-primary-900/40 dark:text-white' => request()->routeIs('admin.settings.categories.*'),
                    'border-transparent text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-white/5 dark:hover:text-white' => ! request()->routeIs('admin.settings.categories.*'),
                ])
            >
                <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z" />
                </svg>
                <span x-show="! sidebarCollapsed" x-transition.opacity.duration.150ms class="truncate">
                    {{ __('nav.categories') }}
                </span>
            </a>
        @endcan

        @can('settings.manage')
            <a
                href="{{ route('admin.settings.aid-programs.index') }}"
                wire:navigate
                title="{{ __('nav.aid_programs') }}"
                style="animation: fade-in-up 0.3s ease-out both; animation-delay: 260ms"
                @class([
                    'group flex items-center gap-3 rounded-(--radius-brand) border-s-4 px-3 py-2.5 text-sm font-medium transition duration-150 ease-out',
                    'border-primary bg-primary-50 text-primary-800 dark:bg-primary-900/40 dark:text-white' => request()->routeIs('admin.settings.aid-programs.*'),
                    'border-transparent text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-white/5 dark:hover:text-white' => ! request()->routeIs('admin.settings.aid-programs.*'),
                ])
            >
                <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5m6 4.125 2.25 2.25m0 0 2.25-2.25m-2.25 2.25V6.75m-8.25.75h16.5m-9-3H12a2.25 2.25 0 0 0-2.25 2.25v.75h4.5v-.75A2.25 2.25 0 0 0 12 3.75Z" />
                </svg>
                <span x-show="! sidebarCollapsed" x-transition.opacity.duration.150ms class="truncate">
                    {{ __('nav.aid_programs') }}
                </span>
            </a>
        @endcan

        @can('approvals.configure')
            <a
                href="{{ route('admin.settings.approval-flows.index') }}"
                wire:navigate
                title="{{ __('nav.approval_flows') }}"
                style="animation: fade-in-up 0.3s ease-out both; animation-delay: 280ms"
                @class([
                    'group flex items-center gap-3 rounded-(--radius-brand) border-s-4 px-3 py-2.5 text-sm font-medium transition duration-150 ease-out',
                    'border-primary bg-primary-50 text-primary-800 dark:bg-primary-900/40 dark:text-white' => request()->routeIs('admin.settings.approval-flows.*'),
                    'border-transparent text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-white/5 dark:hover:text-white' => ! request()->routeIs('admin.settings.approval-flows.*'),
                ])
            >
                <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
                <span x-show="! sidebarCollapsed" x-transition.opacity.duration.150ms class="truncate">
                    {{ __('nav.approval_flows') }}
                </span>
            </a>
        @endcan

        @can('notifications.settings.manage')
            <a
                href="{{ route('admin.settings.notifications.index') }}"
                wire:navigate
                title="{{ __('nav.notification_settings') }}"
                style="animation: fade-in-up 0.3s ease-out both; animation-delay: 300ms"
                @class([
                    'group flex items-center gap-3 rounded-(--radius-brand) border-s-4 px-3 py-2.5 text-sm font-medium transition duration-150 ease-out',
                    'border-primary bg-primary-50 text-primary-800 dark:bg-primary-900/40 dark:text-white' => request()->routeIs('admin.settings.notifications.*'),
                    'border-transparent text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-white/5 dark:hover:text-white' => ! request()->routeIs('admin.settings.notifications.*'),
                ])
            >
                <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                </svg>
                <span x-show="! sidebarCollapsed" x-transition.opacity.duration.150ms class="truncate">
                    {{ __('nav.notification_settings') }}
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

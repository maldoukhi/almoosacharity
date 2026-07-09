@props([])

@php
    $otherLocale = app()->getLocale() === 'ar' ? 'en' : 'ar';
    $otherLocaleLabel = \App\Enums\Locale::from($otherLocale)->label();
@endphp

<header class="sticky top-0 z-20 flex h-16 shrink-0 items-center gap-3 border-b border-gray-100 bg-white/90 px-4 backdrop-blur sm:px-6 lg:px-8 dark:border-white/10 dark:bg-primary-950/90">
    <button
        type="button"
        x-on:click="window.innerWidth >= 1024 ? (sidebarCollapsed = ! sidebarCollapsed) : (sidebarOpen = ! sidebarOpen)"
        title="{{ __('common.toggle_sidebar') }}"
        class="inline-flex shrink-0 items-center justify-center rounded-(--radius-brand) p-2 text-gray-500 transition duration-150 hover:bg-gray-100 hover:text-gray-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-primary-500 dark:text-gray-300 dark:hover:bg-white/10 dark:hover:text-white"
    >
        <span class="sr-only">{{ __('common.toggle_sidebar') }}</span>
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" />
        </svg>
    </button>

    <div class="min-w-0 flex-1">
        {{ $slot }}
    </div>

    <div class="flex shrink-0 items-center gap-2">
        @auth
            {{-- Global search palette (Ctrl+K / Cmd+K) --}}
            <livewire:global-search />

            {{-- Notifications bell --}}
            <livewire:notifications.bell />
        @endauth

        {{-- Language switcher --}}
        <form method="POST" action="{{ route('locale.switch', $otherLocale) }}">
            @csrf
            <button
                type="submit"
                class="rounded-(--radius-brand) px-3 py-2 text-sm font-medium text-gray-600 transition duration-150 hover:bg-gray-100 hover:text-gray-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-primary-500 dark:text-gray-300 dark:hover:bg-white/10 dark:hover:text-white"
            >
                {{ $otherLocaleLabel }}
            </button>
        </form>

        {{-- Dark mode toggle --}}
        <button
            type="button"
            x-data="{ dark: document.documentElement.classList.contains('dark') }"
            x-on:click="
                dark = ! dark;
                document.documentElement.classList.toggle('dark', dark);
                localStorage.setItem('theme', dark ? 'dark' : 'light');
            "
            title="{{ __('common.toggle_theme') }}"
            class="inline-flex items-center justify-center rounded-(--radius-brand) p-2 text-gray-500 transition duration-150 hover:bg-gray-100 hover:text-gray-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-primary-500 dark:text-gray-300 dark:hover:bg-white/10 dark:hover:text-white"
        >
            <span class="sr-only">{{ __('common.toggle_theme') }}</span>
            <svg
                x-show="! dark"
                class="h-5 w-5 rotate-0 transition-transform duration-300 ease-out"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="1.5"
                stroke="currentColor"
                aria-hidden="true"
            >
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" />
            </svg>
            <svg
                x-show="dark"
                style="display: none"
                class="h-5 w-5 rotate-0 transition-transform duration-300 ease-out"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="1.5"
                stroke="currentColor"
                aria-hidden="true"
            >
                <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" />
            </svg>
        </button>

        {{-- User menu --}}
        <div x-data="{ open: false }" class="relative">
            <button
                type="button"
                x-on:click="open = ! open"
                x-on:click.outside="open = false"
                class="flex items-center gap-2 rounded-(--radius-brand) p-1.5 pe-2.5 text-sm font-medium text-gray-700 transition duration-150 hover:bg-gray-100 focus-visible:outline focus-visible:outline-2 focus-visible:outline-primary-500 dark:text-gray-200 dark:hover:bg-white/10"
            >
                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-primary-100 text-sm font-semibold text-primary-800 dark:bg-primary-900/60 dark:text-primary-100">
                    {{ mb_substr(auth()->user()->name ?? '', 0, 1) }}
                </span>
                <span class="max-w-[10rem] truncate">{{ auth()->user()->name ?? '' }}</span>
                <svg class="h-4 w-4 shrink-0 text-gray-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
                </svg>
            </button>

            <div
                x-show="open"
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-100"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                style="display: none"
                class="absolute end-0 z-30 mt-2 w-48 rounded-(--radius-brand) bg-white p-1.5 shadow-(--shadow-card) ring-1 ring-gray-100 ltr:origin-top-right rtl:origin-top-left dark:bg-primary-950 dark:ring-white/10"
            >
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button
                        type="submit"
                        class="flex w-full items-center gap-2 rounded-(--radius-brand) px-3 py-2 text-start text-sm text-gray-700 transition duration-150 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-white/5"
                    >
                        <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 9V5.25A2.25 2.25 0 0 1 10.5 3h6a2.25 2.25 0 0 1 2.25 2.25v13.5A2.25 2.25 0 0 1 16.5 21h-6a2.25 2.25 0 0 1-2.25-2.25V15m-3 0-3-3m0 0 3-3m-3 3H15" />
                        </svg>
                        {{ __('nav.logout') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>

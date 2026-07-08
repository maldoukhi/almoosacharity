<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ isset($title) ? $title.' - '.config('app.name') : config('app.name') }}</title>

    <link rel="icon" href="{{ asset('images/brand/logo.svg') }}" type="image/svg+xml">

    {{-- Applied before first paint to avoid a light/dark flash --}}
    <script>
        (function () {
            var stored = localStorage.getItem('theme');
            var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            if (stored === 'dark' || (! stored && prefersDark)) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-gray-50 font-sans text-gray-900 antialiased dark:bg-primary-950 dark:text-gray-100">
    <div
        x-data="{
            sidebarOpen: false,
            sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === 'true',
        }"
        x-effect="localStorage.setItem('sidebarCollapsed', sidebarCollapsed)"
        class="flex h-full min-h-screen"
    >
        <x-layouts.sidebar />

        <div class="flex min-w-0 flex-1 flex-col">
            <x-layouts.topbar>
                @isset($breadcrumbs)
                    <x-layouts.breadcrumbs :items="$breadcrumbs" />
                @endisset
            </x-layouts.topbar>

            <main class="flex-1 overflow-y-auto px-4 py-6 sm:px-6 lg:px-8">
                {{ $slot }}
            </main>
        </div>
    </div>

    <x-ui.toast />

    <x-ui.confirm-dialog />

    @livewire('wire-elements-modal')
</body>
</html>

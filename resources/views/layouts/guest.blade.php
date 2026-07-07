<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ isset($title) ? $title.' - '.config('app.name') : config('app.name') }}</title>

    <link rel="icon" href="{{ asset('images/brand/logo.svg') }}" type="image/svg+xml">

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
<body class="flex h-full min-h-screen items-center justify-center bg-gradient-to-br from-primary-900 via-primary-800 to-primary-700 px-4 py-10 font-sans antialiased dark:from-primary-950 dark:via-primary-900 dark:to-primary-800">
    <div class="w-full max-w-md">
        <div class="mb-8 flex justify-center">
            <span class="inline-flex rounded-2xl bg-white p-4 shadow-(--shadow-card)">
                <img src="{{ asset('images/brand/logo.svg') }}" alt="{{ config('app.name') }}" class="h-10 w-auto">
            </span>
        </div>

        <x-ui.card class="shadow-xl">
            {{ $slot }}
        </x-ui.card>

        <p class="mt-6 text-center text-xs text-primary-100/80">
            &copy; {{ now()->year }} {{ config('app.name') }}
        </p>
    </div>

    <x-ui.toast />
</body>
</html>

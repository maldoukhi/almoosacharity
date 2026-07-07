<!DOCTYPE html>
<html lang="ar" dir="rtl" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
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
<body class="flex min-h-screen flex-col items-center bg-primary-50 px-4 py-8 font-sans antialiased dark:bg-primary-950 sm:py-14">
    <div class="mb-6 flex justify-center">
        <span class="inline-flex rounded-2xl bg-white p-3 shadow-(--shadow-card)">
            <img src="{{ asset('images/brand/logo.svg') }}" alt="{{ config('app.name') }}" class="h-9 w-auto">
        </span>
    </div>

    <main class="w-full max-w-md">
        {{ $slot }}
    </main>

    <p class="mt-8 text-center text-xs text-primary-700/70 dark:text-primary-200/60">
        &copy; {{ now()->year }} {{ config('app.name') }}
    </p>

    <x-ui.toast />
</body>
</html>

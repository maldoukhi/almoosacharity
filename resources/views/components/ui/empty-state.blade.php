@props([
    'title' => null,
    'description' => null,
])

<div {{ $attributes->class([
    'flex flex-col items-center justify-center rounded-(--radius-brand) border border-dashed border-gray-200 bg-white px-6 py-12 text-center dark:border-white/10 dark:bg-primary-950/20',
]) }}>
    @isset($icon)
        <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-primary-50 text-primary-600 dark:bg-primary-900/40 dark:text-primary-300">
            {{ $icon }}
        </div>
    @endisset

    @if ($title)
        <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ $title }}</h3>
    @endif

    @if ($description)
        <p class="mt-1 max-w-sm text-sm text-gray-500 dark:text-gray-400">{{ $description }}</p>
    @endif

    @isset($action)
        <div class="mt-5">{{ $action }}</div>
    @endisset
</div>

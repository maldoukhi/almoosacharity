@props([
    'label' => null,
    'value' => 0,
    'trend' => null,
])

<div {{ $attributes->class([
    'flex items-center gap-4 rounded-(--radius-brand) bg-white p-5 shadow-(--shadow-card) dark:bg-primary-950/40 dark:ring-1 dark:ring-white/5',
]) }}>
    @isset($icon)
        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-primary-50 text-primary-700 dark:bg-primary-900/50 dark:text-primary-200">
            {{ $icon }}
        </div>
    @endisset

    <div class="min-w-0 flex-1">
        <p class="truncate text-sm text-gray-500 dark:text-gray-400">{{ $label }}</p>

        <p
            x-data="{ display: 0, target: {{ (int) $value }} }"
            x-init="
                if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                    display = target;
                } else {
                    let start = null;
                    const duration = 700;
                    const step = (timestamp) => {
                        if (! start) start = timestamp;
                        const progress = Math.min((timestamp - start) / duration, 1);
                        display = Math.floor(progress * target);
                        if (progress < 1) {
                            requestAnimationFrame(step);
                        } else {
                            display = target;
                        }
                    };
                    requestAnimationFrame(step);
                }
            "
            x-text="display.toLocaleString('en-US')"
            class="mt-1 text-2xl font-semibold tabular-nums text-gray-900 dark:text-white"
        >{{ $value }}</p>

        @if ($trend)
            <p class="mt-1 text-xs text-secondary-700 dark:text-secondary-300">{{ $trend }}</p>
        @endif
    </div>
</div>

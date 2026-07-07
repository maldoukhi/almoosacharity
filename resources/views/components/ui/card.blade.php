@props([])

<div {{ $attributes->class([
    'rounded-(--radius-brand) bg-white shadow-(--shadow-card) dark:bg-primary-950/40 dark:ring-1 dark:ring-white/5',
]) }}>
    @isset($header)
        <div class="border-b border-gray-100 px-5 py-4 dark:border-white/10">
            {{ $header }}
        </div>
    @endisset

    <div class="p-5">
        {{ $slot }}
    </div>

    @isset($footer)
        <div class="border-t border-gray-100 px-5 py-4 dark:border-white/10">
            {{ $footer }}
        </div>
    @endisset
</div>

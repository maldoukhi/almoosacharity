@props([])

<div {{ $attributes->class([
    'overflow-x-auto rounded-(--radius-brand) border border-gray-100 dark:border-white/10',
]) }}>
    <table class="w-full min-w-full divide-y divide-gray-100 text-start text-sm tabular-nums dark:divide-white/10">
        {{ $slot }}
    </table>
</div>

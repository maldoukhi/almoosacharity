@props([
    'align' => 'start',
])

<th {{ $attributes->class([
    'whitespace-nowrap bg-gray-50 px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:bg-white/5 dark:text-gray-400',
    'text-start' => $align === 'start',
    'text-end' => $align === 'end',
    'text-center' => $align === 'center',
]) }}>
    {{ $slot }}
</th>

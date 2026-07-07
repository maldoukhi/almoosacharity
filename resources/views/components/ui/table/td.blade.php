@props([
    'align' => 'start',
])

<td {{ $attributes->class([
    'px-4 py-3 text-sm text-gray-700 dark:text-gray-200',
    'text-start' => $align === 'start',
    'text-end' => $align === 'end',
    'text-center' => $align === 'center',
]) }}>
    {{ $slot }}
</td>

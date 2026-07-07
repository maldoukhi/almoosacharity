@props([
    'width' => '100%',
    'height' => '1rem',
    'circle' => false,
])

<div
    {{ $attributes->class([
        'animate-pulse bg-gray-200 motion-reduce:animate-none dark:bg-white/10',
        'rounded-full' => $circle,
        'rounded-(--radius-brand)' => ! $circle,
    ]) }}
    style="width: {{ $width }}; height: {{ $height }};"
    aria-hidden="true"
></div>

@props([
    'label' => null,
    'description' => null,
    'name' => null,
])

@php
    $id = $attributes->get('id', $name);
@endphp

<label
    @if ($id) for="{{ $id }}" @endif
    {{ $attributes->only('class')->class([
        'group inline-flex cursor-pointer items-start gap-3 select-none',
        'opacity-60 cursor-not-allowed' => $attributes->get('disabled'),
    ]) }}
>
    <span class="relative mt-0.5 inline-block h-6 w-11 shrink-0">
        <input
            type="checkbox"
            @if ($id) id="{{ $id }}" @endif
            @if ($name) name="{{ $name }}" @endif
            {{ $attributes->except(['class', 'label', 'description', 'name'])->class(['peer sr-only']) }}
        />

        {{-- Track --}}
        <span
            aria-hidden="true"
            class="absolute inset-0 rounded-full bg-gray-200 transition-colors duration-200 ease-out peer-checked:bg-primary peer-focus-visible:outline peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-primary-500 dark:bg-white/15 dark:peer-checked:bg-primary-500"
        ></span>

        {{-- Knob: uses ltr:/rtl: (not a single peer-checked:translate-x-N) so the
             two directions never fight over the same peer-checked condition and
             the knob always slides toward the visual "on" side regardless of
             document direction. --}}
        <span
            aria-hidden="true"
            class="absolute start-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow transition-transform duration-200 ease-out peer-checked:ltr:translate-x-5 peer-checked:rtl:-translate-x-5"
        ></span>
    </span>

    @if ($label)
        <span class="text-sm font-medium text-gray-700 dark:text-gray-200">
            {{ $label }}

            @if ($description)
                <span class="mt-0.5 block text-xs font-normal text-gray-500 dark:text-gray-400">{{ $description }}</span>
            @endif
        </span>
    @endif
</label>

@props([
    'label' => null,
    'name' => null,
    'options' => [],
    'placeholder' => null,
    'hint' => null,
])

@php
    $id = $attributes->get('id', $name);
    $hasError = $name && $errors->has($name);
@endphp

<div>
    @if ($label)
        <label for="{{ $id }}" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">
            {{ $label }}
        </label>
    @endif

    <div class="relative">
        <select
            id="{{ $id }}"
            @if ($name) name="{{ $name }}" @endif
            {{ $attributes->class([
                'block w-full appearance-none rounded-(--radius-brand) border bg-white ps-3.5 pe-9 py-2.5 text-sm '
                    .'text-gray-900 shadow-sm transition duration-200 ease-out focus:outline-none focus:ring-2 '
                    .'focus:ring-offset-0 disabled:cursor-not-allowed disabled:bg-gray-50 disabled:text-gray-500 '
                    .'dark:bg-primary-950/30 dark:text-gray-100 dark:disabled:bg-white/5',
                'border-status-rejected focus:border-status-rejected focus:ring-status-rejected/30' => $hasError,
                'border-gray-300 focus:border-primary-500 focus:ring-primary-500/30 dark:border-white/10' => ! $hasError,
            ]) }}
        >
            @if ($placeholder)
                <option value="">{{ $placeholder }}</option>
            @endif

            {{ $slot }}

            @foreach ($options as $value => $optionLabel)
                <option value="{{ $value }}">{{ $optionLabel }}</option>
            @endforeach
        </select>

        <svg
            class="pointer-events-none absolute end-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400 dark:text-gray-500"
            viewBox="0 0 20 20"
            fill="currentColor"
            aria-hidden="true"
        >
            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
        </svg>
    </div>

    @if ($hint && ! $hasError)
        <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">{{ $hint }}</p>
    @endif

    @if ($name)
        @error($name)
            <p class="mt-1.5 text-xs text-status-rejected">{{ $message }}</p>
        @enderror
    @endif
</div>

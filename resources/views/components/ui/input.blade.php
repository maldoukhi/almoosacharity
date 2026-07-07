@props([
    'label' => null,
    'name' => null,
    'type' => 'text',
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

    <input
        type="{{ $type }}"
        id="{{ $id }}"
        @if ($name) name="{{ $name }}" @endif
        {{ $attributes->class([
            'block w-full rounded-(--radius-brand) border bg-white px-3.5 py-2.5 text-sm text-gray-900 shadow-sm '
                .'transition duration-200 ease-out placeholder:text-gray-400 focus:outline-none focus:ring-2 '
                .'focus:ring-offset-0 disabled:cursor-not-allowed disabled:bg-gray-50 disabled:text-gray-500 '
                .'dark:bg-primary-950/30 dark:text-gray-100 dark:placeholder:text-gray-500 dark:disabled:bg-white/5',
            'border-status-rejected focus:border-status-rejected focus:ring-status-rejected/30' => $hasError,
            'border-gray-300 focus:border-primary-500 focus:ring-primary-500/30 dark:border-white/10' => ! $hasError,
        ]) }}
    />

    @if ($hint && ! $hasError)
        <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">{{ $hint }}</p>
    @endif

    @if ($name)
        @error($name)
            <p class="mt-1.5 text-xs text-status-rejected">{{ $message }}</p>
        @enderror
    @endif
</div>

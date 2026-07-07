@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button',
    'href' => null,
])

@php
    $base = 'relative inline-flex items-center justify-center gap-2 font-medium rounded-(--radius-brand) '
        .'transition-all duration-200 ease-out focus-visible:outline focus-visible:outline-2 '
        .'focus-visible:outline-offset-2 disabled:opacity-60 disabled:cursor-not-allowed select-none';

    $variants = [
        'primary' => 'bg-primary text-white hover:bg-primary-600 active:bg-primary-700 focus-visible:outline-primary-500',
        'secondary' => 'bg-secondary text-white hover:bg-secondary-600 active:bg-secondary-700 focus-visible:outline-secondary-500',
        'ghost' => 'bg-transparent text-primary-700 hover:bg-primary-50 active:bg-primary-100 '
            .'dark:text-primary-200 dark:hover:bg-primary-900/40 dark:active:bg-primary-900/60 focus-visible:outline-primary-500',
        'danger' => 'bg-status-rejected text-white hover:brightness-110 active:brightness-95 focus-visible:outline-status-rejected',
    ];

    $sizes = [
        'sm' => 'text-sm px-3 py-1.5',
        'md' => 'text-sm px-4 py-2.5',
        'lg' => 'text-base px-5 py-3',
    ];

    $classes = trim($base.' '.($variants[$variant] ?? $variants['primary']).' '.($sizes[$size] ?? $sizes['md']));

    // Livewire auto-scopes wire:loading/wire:target to the action triggered by
    // this same element (wire:click) unless an explicit wire:target is given.
    $wireClick = $attributes->get('wire:click');
    $explicitTarget = $attributes->get('wire:target');
    $loadingTarget = $explicitTarget ?? ($wireClick ? \Illuminate\Support\Str::before($wireClick, '(') : null);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class([$classes]) }}>
        {{ $slot }}
    </a>
@else
    <button
        type="{{ $type }}"
        wire:loading.attr="disabled"
        @if ($loadingTarget) wire:target="{{ $loadingTarget }}" @endif
        {{ $attributes->class([$classes]) }}
    >
        <span
            wire:loading
            @if ($loadingTarget) wire:target="{{ $loadingTarget }}" @endif
            class="h-4 w-4 shrink-0 animate-spin rounded-full border-2 border-current border-t-transparent motion-reduce:animate-none"
            aria-hidden="true"
        ></span>

        <span
            wire:loading.remove
            @if ($loadingTarget) wire:target="{{ $loadingTarget }}" @endif
            class="contents"
        >
            {{ $slot }}
        </span>
    </button>
@endif

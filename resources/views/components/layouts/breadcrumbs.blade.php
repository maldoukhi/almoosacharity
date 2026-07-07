@props([
    'items' => [],
])

<nav aria-label="{{ __('ui.breadcrumbs') }}" {{ $attributes->class(['flex items-center text-sm text-gray-500 dark:text-gray-400']) }}>
    <ol class="flex flex-wrap items-center gap-1">
        @foreach ($items as $label => $url)
            <li class="flex items-center gap-1">
                @if (! $loop->first)
                    <svg class="h-4 w-4 shrink-0 text-gray-300 rtl:rotate-180 dark:text-gray-600" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 0 1 .02-1.06L11.168 10 7.23 6.29a.75.75 0 1 1 1.04-1.08l4.5 4.25a.75.75 0 0 1 0 1.08l-4.5 4.25a.75.75 0 0 1-1.06-.02Z" clip-rule="evenodd" />
                    </svg>
                @endif

                @if ($url && ! $loop->last)
                    <a href="{{ $url }}" wire:navigate class="transition duration-150 hover:text-primary-700 dark:hover:text-primary-300">
                        {{ $label }}
                    </a>
                @else
                    <span class="font-medium text-gray-700 dark:text-gray-200" aria-current="page">
                        {{ $label }}
                    </span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>

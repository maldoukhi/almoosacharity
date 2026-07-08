@props([
    // Ordered map of tab-key => label.
    'tabs' => [],
    // Currently active tab key. Only used in "wire" mode (see $wireClick below).
    'active' => null,
    // Method name to call via wire:click for server-driven tabs (e.g. Show).
    // When omitted, the component falls back to a pure Alpine mode
    // that expects an ancestor `x-data="{ activeTab: '...' }"` scope and simply
    // toggles that variable — panels must then be shown with `x-show="activeTab === '...'"`.
    'wireClick' => null,
    // Optional map of tab-key => bool to render a small error indicator dot.
    // Named tabErrors (not `errors`) to avoid shadowing Laravel's shared
    // ViewErrorBag when the prop is omitted.
    'tabErrors' => [],
])

<div class="flex gap-1 overflow-x-auto overflow-y-hidden border-b border-gray-100 dark:border-white/10" role="tablist">
    @foreach ($tabs as $key => $label)
        @php $hasError = $tabErrors[$key] ?? false; @endphp

        <button
            type="button"
            role="tab"
            @if ($wireClick)
                wire:click="{{ $wireClick }}('{{ $key }}')"
                @class([
                    'group relative inline-flex shrink-0 items-center gap-1.5 whitespace-nowrap px-4 py-3 text-sm font-medium transition-colors duration-200 ease-out focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-500',
                    'text-primary-700 dark:text-primary-200' => $active === $key,
                    'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' => $active !== $key,
                ])
            @else
                x-on:click="activeTab = @js($key)"
                :class="activeTab === @js($key) ? 'text-primary-700 dark:text-primary-200' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                class="group relative inline-flex shrink-0 items-center gap-1.5 whitespace-nowrap px-4 py-3 text-sm font-medium transition-colors duration-200 ease-out focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-500"
            @endif
        >
            <span>{{ $label }}</span>

            @if ($hasError)
                <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-status-rejected" aria-hidden="true"></span>
            @endif

            @if ($wireClick)
                <span
                    @class([
                        'absolute inset-x-3 bottom-0 h-0.5 rounded-full bg-primary transition-opacity duration-200 ease-out',
                        'opacity-100' => $active === $key,
                        'opacity-0' => $active !== $key,
                    ])
                    aria-hidden="true"
                ></span>
            @else
                <span
                    class="absolute inset-x-3 bottom-0 h-0.5 rounded-full bg-primary transition-opacity duration-200 ease-out"
                    :class="activeTab === @js($key) ? 'opacity-100' : 'opacity-0'"
                    aria-hidden="true"
                ></span>
            @endif
        </button>
    @endforeach
</div>

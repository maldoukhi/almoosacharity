@props(['section'])

{{-- Contextual help: opens a modal showing ONLY this screen's section of the
     user guide (iframe onto the section endpoint, loaded lazily on first
     open). Plain fixed-inset overlay — no @teleport, it breaks Livewire
     morph in this app. --}}
<div x-data="{ open: false, src: '' }" x-on:keydown.window.escape="open = false" class="inline-block">
    <button
        type="button"
        x-on:click="src = src || '{{ route('help.user-guide.section', $section) }}'; open = true"
        title="{{ __('nav.help_button') }}"
        {{ $attributes->class('inline-flex shrink-0 items-center gap-1.5 rounded-full border border-gray-200 px-3 py-1.5 text-xs font-medium text-gray-500 transition duration-150 hover:border-primary-300 hover:bg-primary-50 hover:text-primary-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-primary-500 dark:border-white/10 dark:text-gray-400 dark:hover:bg-primary-500/10 dark:hover:text-primary-300') }}
    >
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 5.25h.008v.008H12v-.008Z" />
        </svg>
        {{ __('nav.help_button') }}
    </button>

    <div
        x-show="open"
        x-cloak
        style="display: none"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-8"
        role="dialog"
        aria-modal="true"
        aria-label="{{ __('nav.help_button') }}"
    >
        <div class="absolute inset-0 bg-primary-950/60 backdrop-blur-sm" x-on:click="open = false"></div>

        <div class="relative flex h-full max-h-[85vh] w-full max-w-4xl flex-col overflow-hidden rounded-(--radius-brand) bg-white shadow-2xl dark:bg-primary-950">
            <div class="flex items-center justify-between gap-3 border-b border-gray-100 px-5 py-3 dark:border-white/10">
                <h2 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('nav.help_button') }}</h2>
                <div class="flex items-center gap-2">
                    <a
                        href="{{ route('help.user-guide') }}#{{ $section }}"
                        target="_blank"
                        rel="noopener"
                        class="text-xs font-medium text-primary-700 hover:underline dark:text-primary-300"
                    >
                        {{ __('nav.help_full_guide') }}
                    </a>
                    <button
                        type="button"
                        x-on:click="open = false"
                        class="rounded-full p-1.5 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-white/10 dark:hover:text-gray-200"
                    >
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                        <span class="sr-only">{{ __('common.cancel') }}</span>
                    </button>
                </div>
            </div>

            <iframe
                :src="src"
                title="{{ __('nav.help_button') }}"
                class="h-full w-full flex-1 bg-white"
                loading="lazy"
            ></iframe>
        </div>
    </div>
</div>

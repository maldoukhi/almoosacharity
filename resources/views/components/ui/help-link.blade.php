@props(['section'])

{{-- Contextual help: opens the in-platform user guide at this screen's
     section. Plain anchor (new tab) — the guide is a standalone document. --}}
<a
    href="{{ route('help.user-guide') }}#{{ $section }}"
    target="_blank"
    rel="noopener"
    title="{{ __('nav.help_button') }}"
    {{ $attributes->class('inline-flex shrink-0 items-center gap-1.5 rounded-full border border-gray-200 px-3 py-1.5 text-xs font-medium text-gray-500 transition duration-150 hover:border-primary-300 hover:bg-primary-50 hover:text-primary-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-primary-500 dark:border-white/10 dark:text-gray-400 dark:hover:bg-primary-500/10 dark:hover:text-primary-300') }}
>
    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 5.25h.008v.008H12v-.008Z" />
    </svg>
    {{ __('nav.help_button') }}
</a>

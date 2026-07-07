@props([
    'name' => null,
])

{{--
    Usage:
      <x-ui.modal name="delete-user">
          ...content...
      </x-ui.modal>

    Open/close from anywhere via browser events:
      $dispatch('open-modal', 'delete-user')
      $dispatch('close-modal', 'delete-user')   // or with no name to close any open modal
--}}

<div
    x-data="{ open: false }"
    x-on:open-modal.window="if ($event.detail === @js($name)) open = true"
    x-on:close-modal.window="if (! $event.detail || $event.detail === @js($name)) open = false"
    x-on:keydown.escape.window="open = false"
    x-show="open"
    style="display: none"
    class="fixed inset-0 z-50 grid place-items-center p-4"
>
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        x-on:click="open = false"
        class="fixed inset-0 bg-primary-950/60 backdrop-blur-sm"
        aria-hidden="true"
    ></div>

    <div
        x-show="open"
        x-on:click.stop
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        role="dialog"
        aria-modal="true"
        {{ $attributes->class([
            'relative z-10 w-full max-w-lg rounded-(--radius-brand) bg-white p-6 shadow-(--shadow-card) dark:bg-primary-950 dark:ring-1 dark:ring-white/10',
        ]) }}
    >
        <button
            type="button"
            x-on:click="open = false"
            class="absolute end-4 top-4 inline-flex rounded-full p-1.5 text-gray-400 transition duration-150 hover:bg-gray-100 hover:text-gray-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-500 dark:hover:bg-white/10 dark:hover:text-gray-200"
        >
            <span class="sr-only">{{ __('common.close') }}</span>
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5" aria-hidden="true">
                <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" />
            </svg>
        </button>

        {{ $slot }}
    </div>
</div>

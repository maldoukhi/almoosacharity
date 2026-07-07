@props([])

<div
    x-data="{
        toasts: [],
        add(detail) {
            const id = Date.now() + Math.random();
            this.toasts.push({ id, type: detail?.type ?? 'info', message: detail?.message ?? '' });
            setTimeout(() => this.remove(id), 4000);
        },
        remove(id) {
            this.toasts = this.toasts.filter((toast) => toast.id !== id);
        },
    }"
    x-on:toast.window="add(Array.isArray($event.detail) ? $event.detail[0] : $event.detail)"
    class="pointer-events-none fixed bottom-4 start-4 z-[100] flex w-full max-w-sm flex-col gap-2"
    aria-live="polite"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div
            x-show="true"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="pointer-events-auto flex w-full items-start gap-3 rounded-(--radius-brand) bg-white p-4 shadow-(--shadow-card) ring-1 dark:bg-primary-950 dark:text-gray-100"
            :class="{
                'ring-status-approved/30': toast.type === 'success',
                'ring-status-rejected/30': toast.type === 'error',
                'ring-primary-300/40 dark:ring-primary-700/40': toast.type === 'info',
            }"
        >
            <span
                class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full"
                :class="{
                    'bg-status-approved/15 text-status-approved': toast.type === 'success',
                    'bg-status-rejected/15 text-status-rejected': toast.type === 'error',
                    'bg-primary-100 text-primary-700 dark:bg-primary-900/50 dark:text-primary-200': toast.type === 'info',
                }"
                aria-hidden="true"
            >
                <svg x-show="toast.type === 'success'" viewBox="0 0 20 20" fill="currentColor" class="h-3.5 w-3.5">
                    <path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 0 1.4l-7.4 7.4a1 1 0 0 1-1.4 0l-3.6-3.6a1 1 0 1 1 1.4-1.4l2.9 2.9 6.7-6.7a1 1 0 0 1 1.4 0Z" clip-rule="evenodd" />
                </svg>
                <svg x-show="toast.type === 'error'" viewBox="0 0 20 20" fill="currentColor" class="h-3.5 w-3.5">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm.75-11.5a.75.75 0 0 0-1.5 0v4a.75.75 0 0 0 1.5 0v-4ZM10 13.5a.75.75 0 1 0 0 1.5.75.75 0 0 0 0-1.5Z" clip-rule="evenodd" />
                </svg>
                <svg x-show="toast.type === 'info'" viewBox="0 0 20 20" fill="currentColor" class="h-3.5 w-3.5">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0ZM9.25 8.75a.75.75 0 0 1 .75-.75h.008a.75.75 0 0 1 .75.75V13.5a.75.75 0 0 1-1.5 0V8.75Zm.75-3.25a.9.9 0 1 0 0 1.8.9.9 0 0 0 0-1.8Z" clip-rule="evenodd" />
                </svg>
            </span>

            <p class="flex-1 text-sm text-gray-700 dark:text-gray-200" x-text="toast.message"></p>

            <button
                type="button"
                x-on:click="remove(toast.id)"
                class="shrink-0 rounded-full p-1 text-gray-400 transition duration-150 hover:bg-gray-100 hover:text-gray-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-primary-500 dark:hover:bg-white/10 dark:hover:text-gray-200"
            >
                <span class="sr-only">{{ __('common.close') }}</span>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4" aria-hidden="true">
                    <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" />
                </svg>
            </button>
        </div>
    </template>
</div>

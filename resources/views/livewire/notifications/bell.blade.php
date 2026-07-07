@php
    $unread = $this->unreadCount;
@endphp

<div x-data="{ open: false }" class="relative" wire:poll.30s="$refresh">
    <button
        type="button"
        x-on:click="open = ! open"
        x-on:click.outside="open = false"
        title="{{ __('notifications.bell.title') }}"
        class="relative inline-flex items-center justify-center rounded-(--radius-brand) p-2 text-gray-500 transition duration-150 hover:bg-gray-100 hover:text-gray-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-primary-500 dark:text-gray-300 dark:hover:bg-white/10 dark:hover:text-white"
    >
        <span class="sr-only">{{ __('notifications.bell.title') }}</span>

        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
        </svg>

        @if ($unread > 0)
            <span class="absolute -end-0.5 -top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-status-rejected px-1 text-[10px] font-semibold leading-none text-white">
                {{ $unread > 99 ? '99+' : $unread }}
            </span>
        @endif
    </button>

    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        style="display: none"
        class="absolute end-0 z-30 mt-2 w-80 rounded-(--radius-brand) bg-white shadow-(--shadow-card) ring-1 ring-gray-100 ltr:origin-top-right rtl:origin-top-left dark:bg-primary-950 dark:ring-white/10"
    >
        <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3 dark:border-white/10">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('notifications.bell.title') }}</h3>

            @if ($unread > 0)
                <button
                    type="button"
                    wire:click="markAllAsRead"
                    class="text-xs font-medium text-primary-700 hover:underline dark:text-primary-300"
                >
                    {{ __('notifications.bell.mark_all_read') }}
                </button>
            @endif
        </div>

        <div class="max-h-96 overflow-y-auto">
            @forelse ($this->recent as $notification)
                @php
                    $data = $notification->data;
                    $unreadItem = is_null($notification->read_at);
                    $url = $data['url'] ?? '#';
                @endphp

                <a
                    href="{{ $url }}"
                    wire:click="markAsRead('{{ $notification->id }}')"
                    wire:key="bell-notification-{{ $notification->id }}"
                    class="flex flex-col gap-1 border-b border-gray-50 px-4 py-3 text-start transition duration-150 last:border-b-0 hover:bg-gray-50 dark:border-white/5 dark:hover:bg-white/5"
                    @class([
                        'bg-primary-50/50 dark:bg-primary-900/20' => $unreadItem,
                    ])
                >
                    <p class="text-sm text-gray-700 dark:text-gray-200">
                        {{ __('notifications.awaiting_review.body', [
                            'reference' => $data['reference'] ?? '',
                            'beneficiary' => $data['beneficiary_name'] ?? '',
                            'stage' => $data['stage_name'] ?? '',
                        ]) }}
                    </p>
                    <span class="text-xs text-gray-400 dark:text-gray-500">{{ $notification->created_at->diffForHumans() }}</span>
                </a>
            @empty
                <p class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">{{ __('notifications.bell.empty') }}</p>
            @endforelse
        </div>

        @if (\Illuminate\Support\Facades\Route::has('notifications.index'))
            <div class="border-t border-gray-100 px-4 py-2.5 dark:border-white/10">
                <a
                    href="{{ route('notifications.index') }}"
                    wire:navigate
                    class="block text-center text-xs font-medium text-primary-700 hover:underline dark:text-primary-300"
                >
                    {{ __('notifications.bell.view_all') }}
                </a>
            </div>
        @endif
    </div>
</div>

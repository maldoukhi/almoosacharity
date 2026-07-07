<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ __('notifications.index.title') }}</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('notifications.index.subtitle') }}</p>
        </div>

        <x-ui.button type="button" variant="ghost" wire:click="markAllAsRead">
            {{ __('notifications.index.mark_all_read') }}
        </x-ui.button>
    </div>

    <x-ui.card>
        <div class="flex gap-2">
            <button
                type="button"
                wire:click="$set('filter', 'all')"
                class="rounded-(--radius-brand) px-3 py-1.5 text-sm font-medium transition duration-150 {{ $filter === 'all' ? 'bg-primary text-white' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-white/10' }}"
            >
                {{ __('notifications.index.filter_all') }}
            </button>

            <button
                type="button"
                wire:click="$set('filter', 'unread')"
                class="rounded-(--radius-brand) px-3 py-1.5 text-sm font-medium transition duration-150 {{ $filter === 'unread' ? 'bg-primary text-white' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-white/10' }}"
            >
                {{ __('notifications.index.filter_unread') }}
            </button>
        </div>
    </x-ui.card>

    <x-ui.card>
        @if ($this->notifications->isEmpty())
            <x-ui.empty-state :title="__('notifications.index.empty_title')" :description="__('notifications.index.empty_description')" />
        @else
            <div class="divide-y divide-gray-100 dark:divide-white/10">
                @foreach ($this->notifications as $notification)
                    @php
                        $data = $notification->data;
                        $unreadItem = is_null($notification->read_at);
                    @endphp

                    <div
                        wire:key="notification-{{ $notification->id }}"
                        class="flex flex-col gap-2 py-4 sm:flex-row sm:items-center sm:justify-between"
                        @class([
                            '-mx-5 px-5 bg-primary-50/50 dark:bg-primary-900/20' => $unreadItem,
                        ])
                    >
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                @if ($unreadItem)
                                    <span class="h-2 w-2 shrink-0 rounded-full bg-primary" aria-hidden="true"></span>
                                @endif

                                <p class="text-sm text-gray-700 dark:text-gray-200">
                                    {{ __($data['lang_key'] ?? 'notifications.awaiting_review.body', [
                                        'reference' => $data['reference'] ?? '',
                                        'beneficiary' => $data['beneficiary_name'] ?? '',
                                        'stage' => $data['stage_name'] ?? '',
                                    ]) }}
                                </p>
                            </div>
                            <span class="text-xs text-gray-400 dark:text-gray-500">{{ $notification->created_at->diffForHumans() }}</span>
                        </div>

                        <div class="flex shrink-0 items-center gap-2">
                            @if (! empty($data['url']))
                                <x-ui.button href="{{ $data['url'] }}" variant="ghost" size="sm">
                                    {{ __('common.view') }}
                                </x-ui.button>
                            @endif

                            @if ($unreadItem)
                                <x-ui.button type="button" variant="ghost" size="sm" wire:click="markAsRead('{{ $notification->id }}')">
                                    {{ __('notifications.index.mark_read') }}
                                </x-ui.button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-4">
                {{ $this->notifications->links() }}
            </div>
        @endif
    </x-ui.card>
</div>

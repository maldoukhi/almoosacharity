@php
    $changes = $this->changes;
    $hasChanges = $changes !== [];
@endphp

<div class="p-6">
    <div class="mb-5">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
            {{ __('beneficiaries.activity.modal_title') }}
        </h2>
        <p class="mt-1 text-sm text-gray-700 dark:text-gray-200">
            {{ $this->activityTitle($activity) }}
            <span class="font-normal text-gray-500 dark:text-gray-400">
                &mdash; {{ __('beneficiaries.activity.by', ['actor' => $this->activityCauserName($activity)]) }}
            </span>
        </p>
        <p class="mt-1 text-xs tabular-nums text-gray-400 dark:text-gray-500">
            {{ $activity->created_at->translatedFormat('Y/m/d H:i') }}
            &middot; {{ $activity->created_at->diffForHumans() }}
        </p>
    </div>

    @if ($hasChanges)
        <div>
            <h3 class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-200">
                {{ __('beneficiaries.activity.changes_title') }}
            </h3>

            <ul class="divide-y divide-gray-100 overflow-hidden rounded-(--radius-brand) border border-gray-100 dark:divide-white/10 dark:border-white/10">
                @foreach ($changes as $change)
                    <li wire:key="activity-change-{{ $loop->index }}" class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 text-sm">
                        <span class="font-medium text-gray-700 dark:text-gray-200">{{ $change['field'] }}</span>

                        <div class="flex flex-wrap items-center gap-2">
                            @if ($change['old'] !== null)
                                <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs text-gray-500 line-through dark:bg-white/10 dark:text-gray-400">
                                    {{ $change['old'] }}
                                </span>

                                <svg class="h-4 w-4 shrink-0 text-gray-400 rtl:-scale-x-100" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.25 8.25 21 12m0 0-3.75 3.75M21 12H3" />
                                </svg>
                            @endif

                            <span class="rounded-full bg-status-approved/10 px-2.5 py-1 text-xs font-medium text-status-approved">
                                {{ $change['new'] ?? __('common.dash') }}
                            </span>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    @else
        <p class="rounded-(--radius-brand) bg-gray-50 p-4 text-sm text-gray-500 dark:bg-white/5 dark:text-gray-400">
            {{ __('beneficiaries.activity.no_changes') }}
        </p>
    @endif

    <div class="mt-6 flex items-center justify-end border-t border-gray-100 pt-5 dark:border-white/10">
        <x-ui.button type="button" variant="ghost" wire:click="closeModal">
            {{ __('common.close') }}
        </x-ui.button>
    </div>
</div>

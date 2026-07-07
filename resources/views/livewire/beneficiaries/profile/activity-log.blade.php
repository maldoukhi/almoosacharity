<div class="space-y-5">
    <h2 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('beneficiaries.activity.title') }}</h2>

    @if ($this->activities->isEmpty())
        <x-ui.empty-state :title="__('beneficiaries.activity.empty_title')" :description="__('beneficiaries.activity.empty_description')" />
    @else
        <ol class="relative space-y-6 border-s-2 border-gray-100 ps-6 dark:border-white/10">
            @foreach ($this->activities as $activity)
                @php
                    $isBankReveal = $activity->log_name === 'bank-data-reveal';
                    $isDeleted = $activity->event === 'deleted';
                    $isCreatedOrRestored = in_array($activity->event, ['created', 'restored'], true);

                    $iconColor = match (true) {
                        $isBankReveal => 'bg-status-review/15 text-status-review',
                        $isDeleted => 'bg-status-rejected/15 text-status-rejected',
                        $isCreatedOrRestored => 'bg-status-approved/15 text-status-approved',
                        default => 'bg-primary-100 text-primary-700 dark:bg-primary-900/40 dark:text-primary-200',
                    };

                    $oldValues = $activity->attribute_changes['old'] ?? [];
                    $newValues = $activity->attribute_changes['attributes'] ?? [];
                @endphp

                <li wire:key="activity-{{ $activity->id }}" class="relative">
                    <span
                        class="absolute -start-[calc(1.5rem+9px)] flex h-4 w-4 items-center justify-center rounded-full ring-4 ring-white dark:ring-primary-950 {{ $iconColor }}"
                        aria-hidden="true"
                    >
                        @if ($isBankReveal)
                            <svg class="h-2.5 w-2.5" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 12a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z" />
                                <path fill-rule="evenodd" d="M.664 10.59a1.65 1.65 0 0 1 0-1.18C1.984 5.766 5.71 3 10 3s8.016 2.766 9.336 6.41c.111.305.111.632 0 .937C18.016 14.238 14.29 17 10 17s-8.016-2.766-9.336-6.41ZM14 10a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z" clip-rule="evenodd" />
                            </svg>
                        @else
                            <svg class="h-2 w-2" fill="currentColor" viewBox="0 0 8 8">
                                <circle cx="4" cy="4" r="4" />
                            </svg>
                        @endif
                    </span>

                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                            {{ $activity->description }}
                            <span class="font-normal text-gray-500 dark:text-gray-400">
                                &mdash; {{ __('beneficiaries.activity.by', ['actor' => $activity->causer?->name ?? __('beneficiaries.activity.system_actor')]) }}
                            </span>
                        </p>

                        <time class="shrink-0 text-xs tabular-nums text-gray-400 dark:text-gray-500">
                            {{ $activity->created_at->diffForHumans() }}
                        </time>
                    </div>

                    @if (! empty($newValues))
                        <ul class="mt-2 space-y-1 rounded-(--radius-brand) bg-gray-50 p-3 text-xs text-gray-600 dark:bg-white/5 dark:text-gray-300">
                            @foreach ($newValues as $field => $newValue)
                                <li>
                                    <span class="font-medium text-gray-700 dark:text-gray-200">{{ __('beneficiaries.field_'.$field) }}:</span>
                                    <span class="tabular-nums">{{ $oldValues[$field] ?? __('common.dash') }}</span>
                                    <span aria-hidden="true">←</span>
                                    <span class="tabular-nums">{{ $newValue ?? __('common.dash') }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </li>
            @endforeach
        </ol>
    @endif
</div>

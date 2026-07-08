<div class="space-y-5">
    <h2 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('beneficiaries.activity.title') }}</h2>

    @if ($this->activities->isEmpty())
        <x-ui.empty-state :title="__('beneficiaries.activity.empty_title')" :description="__('beneficiaries.activity.empty_description')" />
    @else
        <ol class="relative space-y-3 border-s-2 border-gray-100 ps-6 dark:border-white/10">
            @foreach ($this->activities as $activity)
                <li wire:key="activity-{{ $activity->id }}" class="relative">
                    <span
                        class="absolute -start-[calc(1.5rem+9px)] flex h-4 w-4 items-center justify-center rounded-full ring-4 ring-white dark:ring-primary-950 {{ $this->activityIconColor($activity) }}"
                        aria-hidden="true"
                    >
                        <svg class="h-2 w-2" fill="currentColor" viewBox="0 0 8 8">
                            <circle cx="4" cy="4" r="4" />
                        </svg>
                    </span>

                    <div class="flex flex-wrap items-center justify-between gap-3 rounded-(--radius-brand) border border-gray-100 bg-white px-4 py-3 shadow-(--shadow-card) transition duration-150 ease-out dark:border-white/10 dark:bg-white/5">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ $this->activityTitle($activity) }}
                                <span class="font-normal text-gray-500 dark:text-gray-400">
                                    &mdash; {{ __('beneficiaries.activity.by', ['actor' => $this->activityCauserName($activity)]) }}
                                </span>
                            </p>
                            <time class="text-xs tabular-nums text-gray-400 dark:text-gray-500">
                                {{ $activity->created_at->diffForHumans() }}
                            </time>
                        </div>

                        <x-ui.button
                            type="button"
                            variant="ghost"
                            size="sm"
                            x-on:click="$dispatch('openModal', { component: 'beneficiaries.profile.activity-detail-modal', arguments: { beneficiary: {{ $beneficiary->id }}, activityId: {{ $activity->id }} } })"
                        >
                            {{ __('beneficiaries.activity.details_button') }}
                        </x-ui.button>
                    </div>
                </li>
            @endforeach
        </ol>
    @endif
</div>

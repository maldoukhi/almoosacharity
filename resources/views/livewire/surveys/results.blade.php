<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ __('surveys.results_title') }}</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $survey->title }} &middot; {{ __('surveys.results_subtitle') }}</p>
        </div>

        <div class="flex items-center gap-2">
            <span title="{{ __('surveys.results.export_hint') }}">
                <x-ui.button type="button" variant="ghost" disabled>
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    {{ __('surveys.results.export_excel') }}
                </x-ui.button>
            </span>

            <x-ui.button href="{{ route('admin.surveys.index') }}" variant="ghost">
                {{ __('common.back') }}
            </x-ui.button>
        </div>
    </div>

    <x-ui.stat-card :label="__('surveys.results.total_responses')" :value="$this->totalResponses" />

    {{-- View switch: aggregate summary vs. individual responses --}}
    <div role="tablist" aria-label="{{ __('surveys.results.view_label') }}" class="inline-flex rounded-(--radius-brand) border border-gray-200 bg-gray-50 p-1 dark:border-white/10 dark:bg-white/5">
        <button
            type="button"
            role="tab"
            wire:click="switchView('aggregate')"
            @if ($view === 'aggregate') aria-selected="true" @endif
            class="rounded-[calc(var(--radius-brand)-0.25rem)] px-4 py-1.5 text-sm font-medium transition-colors {{ $view === 'aggregate' ? 'bg-white text-primary shadow-sm dark:bg-white/10 dark:text-white' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' }}"
        >
            {{ __('surveys.results.view_aggregate') }}
        </button>
        <button
            type="button"
            role="tab"
            wire:click="switchView('individual')"
            @if ($view === 'individual') aria-selected="true" @endif
            class="rounded-[calc(var(--radius-brand)-0.25rem)] px-4 py-1.5 text-sm font-medium transition-colors {{ $view === 'individual' ? 'bg-white text-primary shadow-sm dark:bg-white/10 dark:text-white' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' }}"
        >
            {{ __('surveys.results.view_individual') }}
        </button>
    </div>

    @if ($this->totalResponses === 0)
        <x-ui.empty-state :title="__('surveys.results.no_responses_title')" :description="__('surveys.results.no_responses_description')">
            <x-slot:icon>
                <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z" />
                </svg>
            </x-slot:icon>
        </x-ui.empty-state>
    @elseif ($view === 'individual')
        {{-- Individual responses: one row per submission, expandable to full answers --}}
        <div class="space-y-3">
            @foreach ($this->responses as $response)
                @php($beneficiaryName = $response->beneficiary?->short_name)
                <x-ui.card wire:key="survey-response-{{ $response->id }}">
                    <button
                        type="button"
                        wire:click="toggleResponse({{ $response->id }})"
                        aria-expanded="{{ $selectedResponseId === $response->id ? 'true' : 'false' }}"
                        class="flex w-full items-center justify-between gap-3 text-start"
                    >
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-gray-900 dark:text-white">
                                {{ $beneficiaryName !== null && $beneficiaryName !== '' ? $beneficiaryName : __('surveys.results.anonymous') }}
                            </p>
                            <p class="mt-0.5 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-gray-500 dark:text-gray-400">
                                @if ($response->submitted_at)
                                    <span class="tabular-nums">{{ __('surveys.results.submitted_on', ['date' => $response->submitted_at->translatedFormat('Y/m/d')]) }}</span>
                                @endif
                                @if ($response->aid)
                                    <span aria-hidden="true">&middot;</span>
                                    @can('aids.view')
                                        <a
                                            href="{{ route('aids.show', $response->aid) }}"
                                            wire:click.stop
                                            class="text-primary underline-offset-2 hover:underline dark:text-secondary-300"
                                        >
                                            {{ __('surveys.results.related_aid', ['reference' => $response->aid->reference]) }}
                                        </a>
                                    @else
                                        <span>{{ __('surveys.results.related_aid', ['reference' => $response->aid->reference]) }}</span>
                                    @endcan
                                @endif
                            </p>
                        </div>

                        <svg
                            class="h-5 w-5 shrink-0 text-gray-400 transition-transform {{ $selectedResponseId === $response->id ? 'rotate-180' : '' }}"
                            fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                        </svg>
                    </button>

                    @if ($selectedResponseId === $response->id)
                        <div class="mt-4 space-y-4 border-t border-gray-100 pt-4 dark:border-white/10">
                            @foreach ($this->selectedResponseDetail as $item)
                                <div wire:key="survey-response-{{ $response->id }}-answer-{{ $item['id'] }}" class="rounded-(--radius-brand) border border-gray-100 bg-gray-50 px-3.5 py-3 dark:border-white/10 dark:bg-white/5">
                                    <div class="mb-2 flex items-start justify-between gap-3">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $item['label'] }}</p>
                                        <x-ui.badge color="accent">{{ $item['type_label'] }}</x-ui.badge>
                                    </div>

                                    @if (! $item['answered'])
                                        <p class="text-sm text-gray-400 dark:text-gray-500">{{ __('surveys.aid_detail.no_answer') }}</p>
                                    @elseif ($item['kind'] === 'rating')
                                        <div class="flex items-center gap-1.5">
                                            <div class="flex items-center gap-0.5" aria-hidden="true">
                                                @for ($star = 1; $star <= $item['max_stars']; $star++)
                                                    <svg
                                                        class="h-5 w-5 {{ $star <= $item['rating'] ? 'text-accent-500' : 'text-gray-200 dark:text-white/10' }}"
                                                        fill="currentColor" viewBox="0 0 20 20"
                                                    >
                                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.958a1 1 0 0 0 .95.69h4.162c.969 0 1.371 1.24.588 1.81l-3.367 2.446a1 1 0 0 0-.364 1.118l1.287 3.957c.3.922-.755 1.688-1.54 1.118l-3.366-2.446a1 1 0 0 0-1.176 0l-3.367 2.446c-.784.57-1.838-.196-1.539-1.118l1.286-3.957a1 1 0 0 0-.363-1.118L2.983 9.385c-.783-.57-.38-1.81.588-1.81h4.163a1 1 0 0 0 .95-.69l1.285-3.958Z" />
                                                    </svg>
                                                @endfor
                                            </div>
                                            <span class="text-sm tabular-nums text-gray-500 dark:text-gray-400">{{ $item['rating'] }}/{{ $item['max_stars'] }}</span>
                                        </div>
                                    @elseif ($item['kind'] === 'yes_no')
                                        <x-ui.badge :color="$item['yes'] ? 'approved' : 'rejected'">
                                            {{ $item['yes'] ? __('surveys.aid_detail.yes') : __('surveys.aid_detail.no') }}
                                        </x-ui.badge>
                                    @elseif ($item['kind'] === 'choice')
                                        <div class="flex flex-wrap gap-1.5">
                                            @foreach ($item['labels'] as $label)
                                                <x-ui.badge color="primary">{{ $label }}</x-ui.badge>
                                            @endforeach
                                        </div>
                                    @else
                                        <p class="whitespace-pre-line text-sm text-gray-700 dark:text-gray-200">{{ $item['text'] }}</p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </x-ui.card>
            @endforeach

            <div>
                {{ $this->responses->links() }}
            </div>
        </div>
    @else
        <div class="space-y-4">
            @foreach ($this->perQuestion as $result)
                <x-ui.card wire:key="survey-result-question-{{ $result['question']->id }}">
                    <x-slot:header>
                        <div class="flex items-center justify-between gap-3">
                            <h3 class="font-medium text-gray-900 dark:text-white">{{ $result['question']->label }}</h3>
                            <x-ui.badge color="accent">{{ $result['question']->type->label() }}</x-ui.badge>
                        </div>
                    </x-slot:header>

                    @if ($result['kind'] === 'choice')
                        <div class="space-y-3">
                            @forelse ($result['options'] as $option)
                                <div>
                                    <div class="mb-1 flex items-center justify-between text-sm text-gray-700 dark:text-gray-200">
                                        <span>{{ $option['label'] }}</span>
                                        <span class="tabular-nums text-gray-500 dark:text-gray-400">{{ $option['count'] }} ({{ $option['percentage'] }}%)</span>
                                    </div>
                                    <div class="h-2.5 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-white/10">
                                        <div class="h-full rounded-full bg-secondary transition-all duration-500 ease-out" style="width: {{ $option['percentage'] }}%"></div>
                                    </div>
                                </div>
                            @empty
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('surveys.results.no_responses_title') }}</p>
                            @endforelse
                        </div>
                    @elseif ($result['kind'] === 'rating')
                        <div class="space-y-4">
                            <div class="flex items-center gap-2">
                                <span class="text-2xl font-semibold text-accent-700 dark:text-accent-300 tabular-nums">
                                    {{ $result['average'] ?? __('common.dash') }}
                                </span>
                                <div class="flex items-center gap-0.5" aria-hidden="true">
                                    @for ($star = 1; $star <= $result['max_stars']; $star++)
                                        <svg
                                            class="h-5 w-5 {{ $result['average'] !== null && $star <= round($result['average']) ? 'text-accent-500' : 'text-gray-200 dark:text-white/10' }}"
                                            fill="currentColor" viewBox="0 0 20 20"
                                        >
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.958a1 1 0 0 0 .95.69h4.162c.969 0 1.371 1.24.588 1.81l-3.367 2.446a1 1 0 0 0-.364 1.118l1.287 3.957c.3.922-.755 1.688-1.54 1.118l-3.366-2.446a1 1 0 0 0-1.176 0l-3.367 2.446c-.784.57-1.838-.196-1.539-1.118l1.286-3.957a1 1 0 0 0-.363-1.118L2.983 9.385c-.783-.57-.38-1.81.588-1.81h4.163a1 1 0 0 0 .95-.69l1.285-3.958Z" />
                                        </svg>
                                    @endfor
                                </div>
                                <span class="text-xs text-gray-500 dark:text-gray-400">({{ __('surveys.results.answers_count', ['count' => $result['total']]) }})</span>
                            </div>

                            <div class="space-y-2">
                                @foreach ($result['distribution'] as $bucket)
                                    <div>
                                        <div class="mb-1 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                                            <span>{{ $bucket['star'] }} ★</span>
                                            <span class="tabular-nums">{{ $bucket['count'] }} ({{ $bucket['percentage'] }}%)</span>
                                        </div>
                                        <div class="h-2 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-white/10">
                                            <div class="h-full rounded-full bg-accent transition-all duration-500 ease-out" style="width: {{ $bucket['percentage'] }}%"></div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @elseif ($result['kind'] === 'yes_no')
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <div class="mb-1 flex items-center justify-between text-sm text-gray-700 dark:text-gray-200">
                                    <span>{{ __('surveys.results.yes_percentage') }}</span>
                                    <span class="tabular-nums text-gray-500 dark:text-gray-400">{{ $result['yes'] }} ({{ $result['yes_percentage'] }}%)</span>
                                </div>
                                <div class="h-2.5 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-white/10">
                                    <div class="h-full rounded-full bg-status-approved transition-all duration-500 ease-out" style="width: {{ $result['yes_percentage'] }}%"></div>
                                </div>
                            </div>

                            <div>
                                <div class="mb-1 flex items-center justify-between text-sm text-gray-700 dark:text-gray-200">
                                    <span>{{ __('surveys.results.no_percentage') }}</span>
                                    <span class="tabular-nums text-gray-500 dark:text-gray-400">{{ $result['no'] }} ({{ $result['no_percentage'] }}%)</span>
                                </div>
                                <div class="h-2.5 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-white/10">
                                    <div class="h-full rounded-full bg-status-rejected transition-all duration-500 ease-out" style="width: {{ $result['no_percentage'] }}%"></div>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="space-y-2">
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('surveys.results.latest_answers') }}</p>

                            @forelse ($result['latest'] as $answer)
                                <div class="rounded-(--radius-brand) border border-gray-100 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-700 dark:border-white/10 dark:bg-white/5 dark:text-gray-200">
                                    {{ $answer['value'] }}
                                    @if ($answer['submitted_at'])
                                        <span class="ms-2 text-xs text-gray-400 dark:text-gray-500">{{ $answer['submitted_at']->translatedFormat('Y/m/d') }}</span>
                                    @endif
                                </div>
                            @empty
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('surveys.results.no_responses_title') }}</p>
                            @endforelse
                        </div>
                    @endif
                </x-ui.card>
            @endforeach
        </div>
    @endif
</div>

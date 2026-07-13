@php
    $surveyOptions = $this->surveyOptions->mapWithKeys(fn ($survey) => [$survey->id => $survey->title]);
    $programOptions = $this->programs->mapWithKeys(fn ($program) => [$program->id => $program->name]);
@endphp

<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ __('reports.surveys.title') }}</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('reports.surveys.subtitle') }}</p>
        </div>

        <div class="flex items-center gap-2">
            <x-ui.help-link section="reports" />

            @can('reports.export')
                <x-ui.button wire:click="exportExcel" variant="secondary" size="sm">
                    {{ __('reports.actions.export_excel') }}
                </x-ui.button>
                <x-ui.button wire:click="exportPdf" variant="ghost" size="sm">
                    {{ __('reports.actions.export_pdf') }}
                </x-ui.button>
            @endcan
        </div>
    </div>

    <x-ui.card>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-ui.select
                :label="__('reports.filters.survey')"
                name="survey"
                wire:model.live="survey"
                :placeholder="__('reports.surveys.all_surveys')"
                :options="$surveyOptions"
            />

            <x-ui.select
                :label="__('reports.filters.program')"
                name="program"
                wire:model.live="program"
                :placeholder="__('common.all')"
                :options="$programOptions"
            />

            <x-ui.input :label="__('reports.filters.from')" name="from" type="date" wire:model.live="from" />
            <x-ui.input :label="__('reports.filters.to')" name="to" type="date" wire:model.live="to" />
        </div>
    </x-ui.card>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <x-ui.stat-card :label="__('reports.surveys.total_surveys')" :value="$this->totals['surveys']" />
        <x-ui.stat-card :label="__('reports.surveys.total_responses')" :value="$this->totals['responses']" />
    </div>

    <div wire:loading.flex wire:target="survey, program, from, to" class="hidden flex-col gap-2" style="display: none">
        <x-ui.skeleton height="6rem" />
        <x-ui.skeleton height="6rem" />
    </div>

    <div wire:loading.remove wire:target="survey, program, from, to" class="space-y-6">
        @forelse ($this->surveys as $summary)
            <x-ui.card wire:key="survey-report-{{ $summary['survey']->id }}">
                <x-slot:header>
                    <div class="flex flex-wrap items-center justify-between gap-x-6 gap-y-1">
                        <h2 class="font-semibold text-gray-900 dark:text-white">{{ $summary['survey']->title }}</h2>
                        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-gray-500 dark:text-gray-400">
                            @if ($summary['program'])
                                <span>{{ __('reports.filters.program') }}: {{ $summary['program'] }}</span>
                            @endif
                            <span class="text-secondary-700 dark:text-secondary-300">
                                {{ __('reports.surveys.responses_count') }}: <span class="tabular-nums">{{ $summary['total_responses'] }}</span>
                            </span>
                            @if ($summary['response_rate'] !== null)
                                <span class="text-accent-700 dark:text-accent-300">
                                    {{ __('reports.surveys.response_rate') }}: <span class="tabular-nums">{{ $summary['response_rate'] }}%</span>
                                </span>
                            @endif
                        </div>
                    </div>
                </x-slot:header>

                @if ($summary['total_responses'] === 0)
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('reports.surveys.no_responses') }}</p>
                @elseif ($summary['questions']->isEmpty())
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('reports.surveys.no_questions') }}</p>
                @else
                    <div class="space-y-6">
                        @foreach ($summary['questions'] as $question)
                            <div wire:key="survey-report-{{ $summary['survey']->id }}-q-{{ $question['question']->id }}" class="border-t border-gray-100 pt-4 first:border-t-0 first:pt-0 dark:border-white/10">
                                <div class="mb-3 flex items-start justify-between gap-3">
                                    <h3 class="text-sm font-medium text-gray-900 dark:text-white">{{ $question['label'] }}</h3>
                                    <x-ui.badge color="accent">{{ $question['type_label'] }}</x-ui.badge>
                                </div>

                                @if ($question['kind'] === 'choice')
                                    <div class="space-y-3">
                                        @forelse ($question['options'] as $option)
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
                                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('reports.surveys.no_responses') }}</p>
                                        @endforelse
                                    </div>
                                @elseif ($question['kind'] === 'rating')
                                    <div class="space-y-4">
                                        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm">
                                            <span class="text-2xl font-semibold text-accent-700 dark:text-accent-300 tabular-nums">
                                                {{ $question['average'] ?? __('common.dash') }}
                                            </span>
                                            <span class="text-gray-500 dark:text-gray-400">{{ __('reports.surveys.metric_average') }} / {{ $question['max_stars'] }}</span>
                                            @if ($question['min'] !== null)
                                                <span class="text-gray-400 dark:text-gray-500 tabular-nums">
                                                    {{ __('reports.surveys.metric_min') }}: {{ $question['min'] }} · {{ __('reports.surveys.metric_max') }}: {{ $question['max'] }}
                                                </span>
                                            @endif
                                        </div>

                                        <div class="space-y-2">
                                            @foreach ($question['distribution'] as $bucket)
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
                                @elseif ($question['kind'] === 'yes_no')
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <div class="mb-1 flex items-center justify-between text-sm text-gray-700 dark:text-gray-200">
                                                <span>{{ __('reports.surveys.yes') }}</span>
                                                <span class="tabular-nums text-gray-500 dark:text-gray-400">{{ $question['yes'] }} ({{ $question['yes_percentage'] }}%)</span>
                                            </div>
                                            <div class="h-2.5 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-white/10">
                                                <div class="h-full rounded-full bg-status-approved transition-all duration-500 ease-out" style="width: {{ $question['yes_percentage'] }}%"></div>
                                            </div>
                                        </div>
                                        <div>
                                            <div class="mb-1 flex items-center justify-between text-sm text-gray-700 dark:text-gray-200">
                                                <span>{{ __('reports.surveys.no') }}</span>
                                                <span class="tabular-nums text-gray-500 dark:text-gray-400">{{ $question['no'] }} ({{ $question['no_percentage'] }}%)</span>
                                            </div>
                                            <div class="h-2.5 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-white/10">
                                                <div class="h-full rounded-full bg-status-rejected transition-all duration-500 ease-out" style="width: {{ $question['no_percentage'] }}%"></div>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <div class="space-y-2">
                                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">
                                            {{ __('reports.surveys.responses_count') }}: {{ $question['total'] }} · {{ __('reports.surveys.latest_answers') }}
                                        </p>
                                        @forelse ($question['latest'] as $sample)
                                            <div class="rounded-(--radius-brand) border border-gray-100 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-700 dark:border-white/10 dark:bg-white/5 dark:text-gray-200">
                                                {{ $sample['text'] }}
                                                @if ($sample['submitted_at'])
                                                    <span class="ms-2 text-xs text-gray-400 dark:text-gray-500">{{ $sample['submitted_at']->translatedFormat('Y/m/d') }}</span>
                                                @endif
                                            </div>
                                        @empty
                                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('reports.surveys.no_responses') }}</p>
                                        @endforelse
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-ui.card>
        @empty
            <x-ui.empty-state :title="__('reports.surveys.empty_title')" :description="__('reports.surveys.empty_description')">
                <x-slot:icon>
                    <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </x-slot:icon>
            </x-ui.empty-state>
        @endforelse
    </div>
</div>

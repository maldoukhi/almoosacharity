@php
    $isEdit = $survey?->exists ?? false;

    $scopeOptions = collect($this->scopes)->mapWithKeys(fn ($case) => [$case->value => $case->label()]);
    $programOptions = $this->programs->mapWithKeys(fn ($program) => [$program->id => $program->name]);

    $typeIcons = [
        'short_text' => 'M4.5 12h15m-15 5.25h15m-15-10.5h15',
        'long_text' => 'M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5',
        'single_choice' => 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
        'multiple_choice' => 'M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z',
        'rating' => 'M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 21.04a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z',
        'yes_no' => 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
    ];
@endphp

<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">
                {{ $isEdit ? __('surveys.edit_title') : __('surveys.create_title') }}
            </h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                {{ $isEdit ? __('surveys.edit_subtitle') : __('surveys.create_subtitle') }}
            </p>
        </div>

        <x-ui.button href="{{ route('admin.surveys.index') }}" variant="ghost">
            {{ __('common.back') }}
        </x-ui.button>
    </div>

    <form wire:submit="save" class="space-y-6">
        <x-ui.card>
            <x-slot:header>
                <h2 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('surveys.builder.survey_details') }}</h2>
            </x-slot:header>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <x-ui.input :label="__('surveys.field_title')" name="title" wire:model="title" />
                </div>

                <div class="sm:col-span-2">
                    <label for="description" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">
                        {{ __('surveys.field_description') }}
                    </label>
                    <textarea
                        id="description"
                        wire:model="description"
                        rows="2"
                        class="block w-full rounded-(--radius-brand) border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 shadow-sm transition duration-200 ease-out focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30 dark:border-white/10 dark:bg-primary-950/30 dark:text-gray-100"
                    ></textarea>
                    @error('description')
                        <p class="mt-1.5 text-xs text-status-rejected">{{ $message }}</p>
                    @enderror
                </div>

                <x-ui.select
                    :label="__('surveys.field_scope')"
                    name="scope"
                    wire:model.live="scope"
                    :options="$scopeOptions"
                />

                @if ($scope === \App\Enums\SurveyScope::Program->value)
                    <div style="animation: fade-in-up 0.2s ease-out both">
                        <x-ui.select
                            :label="__('surveys.field_program')"
                            name="aid_program_id"
                            wire:model="aid_program_id"
                            :placeholder="__('surveys.select_placeholder')"
                            :options="$programOptions"
                        />
                    </div>
                @endif

                <x-ui.input
                    :label="__('surveys.field_starts_at')"
                    name="starts_at"
                    type="datetime-local"
                    wire:model="starts_at"
                />

                <x-ui.input
                    :label="__('surveys.field_ends_at')"
                    name="ends_at"
                    type="datetime-local"
                    wire:model="ends_at"
                />

                <label class="flex cursor-pointer items-center gap-3 select-none">
                    <span class="relative inline-block h-6 w-11 shrink-0">
                        <input type="checkbox" wire:model="is_active" class="peer sr-only" />
                        <span class="absolute inset-0 rounded-full bg-gray-200 transition-colors duration-200 ease-out peer-checked:bg-primary dark:bg-white/10"></span>
                        <span class="absolute start-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow transition-transform duration-200 ease-out peer-checked:translate-x-5 rtl:peer-checked:-translate-x-5"></span>
                    </span>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('surveys.field_is_active') }}</span>
                </label>
            </div>
        </x-ui.card>

        <x-ui.card>
            <x-slot:header>
                <h2 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('surveys.builder.questions_title') }}</h2>
            </x-slot:header>

            <div class="space-y-4">
                @error('questions')
                    <p class="text-xs text-status-rejected">{{ $message }}</p>
                @enderror

                @forelse ($questions as $index => $question)
                    <div
                        wire:key="survey-question-{{ $index }}"
                        style="animation: fade-in-up 0.2s ease-out both"
                        class="rounded-(--radius-brand) border border-gray-200 dark:border-white/10"
                    >
                        <div class="flex items-center justify-between gap-3 border-b border-gray-100 bg-gray-50 px-4 py-2.5 dark:border-white/10 dark:bg-white/5">
                            <div class="flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-200">
                                <svg class="h-4 w-4 text-primary-600 dark:text-primary-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $typeIcons[$question['type']] ?? $typeIcons['short_text'] }}" />
                                </svg>
                                {{ __('surveys.question_type.'.$question['type']) }}
                            </div>

                            <div class="flex items-center gap-1">
                                <x-ui.button type="button" variant="ghost" size="sm" wire:click="moveUp({{ $index }})" :disabled="$index === 0">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 15.75 7.5-7.5 7.5 7.5" />
                                    </svg>
                                    <span class="sr-only">{{ __('surveys.builder.move_up') }}</span>
                                </x-ui.button>

                                <x-ui.button type="button" variant="ghost" size="sm" wire:click="moveDown({{ $index }})" :disabled="$index === count($questions) - 1">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                    </svg>
                                    <span class="sr-only">{{ __('surveys.builder.move_down') }}</span>
                                </x-ui.button>

                                <x-ui.button type="button" variant="danger" size="sm" wire:click="removeQuestion({{ $index }})">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                    </svg>
                                    <span class="sr-only">{{ __('surveys.builder.remove_question') }}</span>
                                </x-ui.button>
                            </div>
                        </div>

                        <div class="space-y-4 p-4">
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <x-ui.input
                                    :label="__('surveys.builder.field_label')"
                                    name="questions.{{ $index }}.label"
                                    wire:model="questions.{{ $index }}.label"
                                    :placeholder="__('surveys.builder.question_label_placeholder')"
                                />

                                <x-ui.input
                                    :label="__('surveys.builder.field_help_text')"
                                    name="questions.{{ $index }}.help_text"
                                    wire:model="questions.{{ $index }}.help_text"
                                />
                            </div>

                            @if ($question['type'] === 'rating')
                                <div class="max-w-xs">
                                    <x-ui.input
                                        :label="__('surveys.builder.field_max_stars')"
                                        type="number"
                                        min="2"
                                        max="10"
                                        name="questions.{{ $index }}.config.max_stars"
                                        wire:model="questions.{{ $index }}.config.max_stars"
                                    />
                                </div>
                            @endif

                            @if (in_array($question['type'], ['single_choice', 'multiple_choice'], true))
                                <div class="space-y-2">
                                    <div class="flex items-center justify-between">
                                        <p class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('surveys.builder.field_options') }}</p>

                                        <x-ui.button type="button" variant="ghost" size="sm" wire:click="addOption({{ $index }})">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                            </svg>
                                            {{ __('surveys.builder.add_option') }}
                                        </x-ui.button>
                                    </div>

                                    @error("questions.{$index}.options")
                                        <p class="text-xs text-status-rejected">{{ $message }}</p>
                                    @enderror

                                    <div class="space-y-2">
                                        @foreach ($question['options'] as $optionIndex => $option)
                                            <div wire:key="survey-question-{{ $index }}-option-{{ $optionIndex }}" class="flex items-end gap-2">
                                                <div class="flex-1">
                                                    <x-ui.input
                                                        :label="__('surveys.builder.field_option_label')"
                                                        name="questions.{{ $index }}.options.{{ $optionIndex }}.label"
                                                        wire:model="questions.{{ $index }}.options.{{ $optionIndex }}.label"
                                                    />
                                                </div>

                                                <div class="w-32">
                                                    <x-ui.input
                                                        :label="__('surveys.builder.field_option_value')"
                                                        name="questions.{{ $index }}.options.{{ $optionIndex }}.value"
                                                        wire:model="questions.{{ $index }}.options.{{ $optionIndex }}.value"
                                                    />
                                                </div>

                                                <x-ui.button type="button" variant="danger" size="sm" wire:click="removeOption({{ $index }}, {{ $optionIndex }})">
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                                    </svg>
                                                    <span class="sr-only">{{ __('surveys.builder.remove_option') }}</span>
                                                </x-ui.button>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <label class="flex cursor-pointer items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                                <input
                                    type="checkbox"
                                    wire:model="questions.{{ $index }}.is_required"
                                    class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-2 focus:ring-primary-500/30 dark:border-white/20"
                                />
                                {{ __('surveys.builder.field_required') }}
                            </label>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('surveys.builder.no_questions_yet') }}</p>
                @endforelse
            </div>

            <div class="mt-5 border-t border-gray-100 pt-4 dark:border-white/10">
                <p class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('surveys.builder.add_question_title') }}</p>

                <div class="flex flex-wrap gap-2">
                    @foreach ($this->questionTypes as $type)
                        <x-ui.button type="button" variant="ghost" size="sm" wire:click="addQuestion('{{ $type->value }}')">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $typeIcons[$type->value] ?? $typeIcons['short_text'] }}" />
                            </svg>
                            {{ $type->label() }}
                        </x-ui.button>
                    @endforeach
                </div>
            </div>
        </x-ui.card>

        <div class="flex flex-col-reverse items-stretch justify-end gap-3 sm:flex-row sm:items-center">
            <x-ui.button href="{{ route('admin.surveys.index') }}" variant="ghost">
                {{ __('common.cancel') }}
            </x-ui.button>

            <x-ui.button type="submit" variant="primary" wire:target="save">
                {{ __('surveys.builder.save') }}
            </x-ui.button>
        </div>
    </form>
</div>

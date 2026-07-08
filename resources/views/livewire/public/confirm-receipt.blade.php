<div>
    @if ($view === 'already')
        <x-ui.card class="text-center">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-status-delivered/10 text-status-delivered">
                <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                </svg>
            </div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('confirmations.already_title') }}</h1>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ __('confirmations.already_description') }}</p>
        </x-ui.card>

    @elseif ($view === 'expired')
        <x-ui.card class="text-center">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-status-review/10 text-status-review">
                <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2m6-2a10 10 0 1 1-20 0 10 10 0 0 1 20 0Z" />
                </svg>
            </div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('confirmations.expired_title') }}</h1>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ __('confirmations.expired_description') }}</p>
        </x-ui.card>

    @elseif ($view === 'not_found')
        <x-ui.card class="text-center">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-status-rejected/10 text-status-rejected">
                <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                </svg>
            </div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('confirmations.not_found_title') }}</h1>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ __('confirmations.not_found_description') }}</p>
        </x-ui.card>

    @elseif ($view === 'confirm')
        <x-ui.card x-data="confirmationSignaturePad()">
            <div class="mb-5 text-center">
                <p class="text-base font-semibold text-gray-900 dark:text-white">
                    {{ __('confirmations.greeting', ['name' => $this->aid->beneficiary?->first_name ?? '']) }}
                </p>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('confirmations.intro') }}</p>
            </div>

            <dl class="space-y-3 rounded-(--radius-brand) bg-gray-50 p-4 text-sm dark:bg-white/5">
                <div class="flex items-center justify-between gap-3">
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('confirmations.field_program') }}</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $this->aid->program?->name ?? __('common.dash') }}</dd>
                </div>

                <div class="flex items-center justify-between gap-3">
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('confirmations.field_type') }}</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $this->aid->type->label() }}</dd>
                </div>

                @if ($this->aid->type === \App\Enums\AidType::InKind && $this->aid->items->isNotEmpty())
                    <div class="flex items-start justify-between gap-3">
                        <dt class="shrink-0 text-gray-500 dark:text-gray-400">{{ __('confirmations.field_items') }}</dt>
                        <dd class="text-end font-medium text-gray-900 dark:text-white">
                            {{ $this->aid->items->pluck('name')->implode('، ') }}
                        </dd>
                    </div>
                @endif

                <div class="flex items-center justify-between gap-3">
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('confirmations.field_delivery_method') }}</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $this->aid->disbursement?->method?->label() ?? __('common.dash') }}</dd>
                </div>

                <div class="flex items-center justify-between gap-3">
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('confirmations.field_delivered_at') }}</dt>
                    <dd class="font-medium tabular-nums text-gray-900 dark:text-white">
                        {{ $this->aid->disbursement?->delivered_at?->translatedFormat('Y/m/d') ?? __('common.dash') }}
                    </dd>
                </div>
            </dl>

            {{-- Optional signature --}}
            <div class="mt-5">
                <div class="mb-1.5 flex items-center justify-between">
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('confirmations.signature') }}</p>
                    <button type="button" @click="clear()" class="text-xs font-medium text-status-rejected hover:underline">
                        {{ __('confirmations.clear_signature') }}
                    </button>
                </div>
                <canvas
                    x-ref="pad"
                    x-init="init()"
                    @pointerdown="startDraw($event)"
                    @pointermove="draw($event)"
                    @pointerup.window="endDraw()"
                    @pointerleave="endDraw()"
                    class="h-40 w-full touch-none rounded-(--radius-brand) border border-dashed border-gray-300 bg-gray-50 dark:border-white/15 dark:bg-white/5"
                ></canvas>
                <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">{{ __('confirmations.signature_hint') }}</p>
            </div>

            <x-ui.button
                type="button"
                variant="secondary"
                size="lg"
                class="mt-6 w-full"
                x-on:click="$wire.set('signature', hasDrawn ? $refs.pad.toDataURL('image/png') : '', false); $wire.confirm()"
                wire:target="confirm"
                wire:loading.attr="disabled"
            >
                {{ __('confirmations.confirm_button') }}
            </x-ui.button>
        </x-ui.card>

    @elseif ($view === 'success')
        <x-ui.card class="text-center">
            <svg class="confirmation-checkmark mx-auto mb-4 h-16 w-16 text-status-delivered" viewBox="0 0 52 52" fill="none" aria-hidden="true">
                <circle class="confirmation-checkmark__circle" cx="26" cy="26" r="24" stroke="currentColor" stroke-width="2" />
                <path class="confirmation-checkmark__check" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" d="M14 27l7 7 17-17" />
            </svg>

            <h1 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('confirmations.success_title') }}</h1>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ __('confirmations.success_description') }}</p>

            <div class="mt-6 flex flex-col gap-2">
                @if ($this->survey)
                    <x-ui.button type="button" variant="secondary" class="w-full" wire:click="startSurvey">
                        {{ $this->survey->is_required ? __('confirmations.start_required_survey_button') : __('confirmations.share_feedback_button') }}
                    </x-ui.button>
                @endif

                {{-- A required survey must be completed: no finish/skip shortcut. --}}
                @unless ($this->survey?->is_required)
                    <x-ui.button type="button" variant="ghost" class="w-full" wire:click="skipSurvey">
                        {{ __('confirmations.finish_button') }}
                    </x-ui.button>
                @endunless
            </div>
        </x-ui.card>

    @elseif ($view === 'survey' && $this->currentQuestion)
        @php $question = $this->currentQuestion; @endphp

        <x-ui.card>
            <div class="mb-5">
                <p class="text-center text-xs font-medium uppercase tracking-wide text-primary-600 dark:text-primary-300">
                    {{ __('confirmations.survey_intro') }}
                </p>

                <div class="mt-3 flex items-center justify-center gap-1.5">
                    @foreach ($this->questions as $index => $q)
                        <span
                            wire:key="survey-dot-{{ $q->id }}"
                            class="h-1.5 rounded-full transition-all duration-200 {{ $index === $step ? 'w-6 bg-primary' : 'w-1.5 bg-gray-200 dark:bg-white/10' }}"
                        ></span>
                    @endforeach
                </div>

                <p class="mt-2 text-center text-xs text-gray-400 dark:text-gray-500">
                    {{ __('confirmations.question_of', ['current' => $step + 1, 'total' => $this->totalSteps]) }}
                </p>
            </div>

            <div wire:key="survey-question-{{ $question->id }}">
                <p class="mb-4 text-center text-base font-medium text-gray-900 dark:text-white">{{ $question->label }}</p>

                @if ($question->help_text)
                    <p class="-mt-3 mb-4 text-center text-xs text-gray-500 dark:text-gray-400">{{ $question->help_text }}</p>
                @endif

                @switch($question->type)
                    @case(\App\Enums\SurveyQuestionType::ShortText)
                        <input
                            type="text"
                            wire:model="answers.{{ $question->id }}"
                            class="block w-full rounded-(--radius-brand) border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30 dark:border-white/10 dark:bg-primary-950/30 dark:text-gray-100"
                        >
                        @break

                    @case(\App\Enums\SurveyQuestionType::LongText)
                        <textarea
                            wire:model="answers.{{ $question->id }}"
                            rows="4"
                            class="block w-full rounded-(--radius-brand) border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30 dark:border-white/10 dark:bg-primary-950/30 dark:text-gray-100"
                        ></textarea>
                        @break

                    @case(\App\Enums\SurveyQuestionType::SingleChoice)
                        <div class="flex flex-col gap-2">
                            @foreach ($question->options ?? [] as $option)
                                <button
                                    type="button"
                                    wire:click="selectAnswer({{ $question->id }}, '{{ $option['value'] }}')"
                                    class="w-full rounded-(--radius-brand) border px-4 py-3 text-start text-sm font-medium transition duration-150 {{ ($answers[$question->id] ?? null) === $option['value'] ? 'border-primary-500 bg-primary-50 text-primary-800 dark:bg-primary-500/20 dark:text-primary-100' : 'border-gray-200 text-gray-700 hover:bg-gray-50 dark:border-white/10 dark:text-gray-200 dark:hover:bg-white/5' }}"
                                >
                                    {{ $option['label'] }}
                                </button>
                            @endforeach
                        </div>
                        @break

                    @case(\App\Enums\SurveyQuestionType::MultipleChoice)
                        <div class="flex flex-col gap-2">
                            @foreach ($question->options ?? [] as $option)
                                <label class="flex w-full cursor-pointer items-center gap-3 rounded-(--radius-brand) border border-gray-200 px-4 py-3 text-sm font-medium text-gray-700 transition duration-150 hover:bg-gray-50 has-checked:border-primary-500 has-checked:bg-primary-50 has-checked:text-primary-800 dark:border-white/10 dark:text-gray-200 dark:hover:bg-white/5 dark:has-checked:bg-primary-500/20 dark:has-checked:text-primary-100">
                                    <input type="checkbox" wire:model="answers.{{ $question->id }}" value="{{ $option['value'] }}" class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                    {{ $option['label'] }}
                                </label>
                            @endforeach
                        </div>
                        @break

                    @case(\App\Enums\SurveyQuestionType::Rating)
                        @php $maxStars = $question->config['max_stars'] ?? 5; @endphp
                        <div class="flex items-center justify-center gap-2" dir="ltr">
                            @for ($star = 1; $star <= $maxStars; $star++)
                                <button
                                    type="button"
                                    wire:click="selectAnswer({{ $question->id }}, {{ $star }})"
                                    aria-label="{{ $star }}"
                                    class="h-10 w-10 shrink-0 transition duration-150 {{ ($answers[$question->id] ?? 0) >= $star ? 'text-accent-500' : 'text-gray-200 dark:text-white/10' }}"
                                >
                                    <svg viewBox="0 0 20 20" fill="currentColor" class="h-full w-full">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.958a1 1 0 0 0 .95.69h4.162c.969 0 1.371 1.24.588 1.81l-3.368 2.447a1 1 0 0 0-.364 1.118l1.287 3.957c.3.922-.755 1.688-1.538 1.118l-3.367-2.447a1 1 0 0 0-1.176 0l-3.367 2.447c-.783.57-1.838-.196-1.538-1.118l1.287-3.957a1 1 0 0 0-.364-1.118L2.063 9.385c-.783-.57-.38-1.81.588-1.81h4.163a1 1 0 0 0 .95-.69z" />
                                    </svg>
                                </button>
                            @endfor
                        </div>
                        @break

                    @case(\App\Enums\SurveyQuestionType::YesNo)
                        <div class="grid grid-cols-2 gap-3">
                            <button
                                type="button"
                                wire:click="selectAnswer({{ $question->id }}, true)"
                                class="rounded-(--radius-brand) border px-4 py-3 text-sm font-semibold transition duration-150 {{ ($answers[$question->id] ?? null) === true ? 'border-secondary-500 bg-secondary-50 text-secondary-700 dark:bg-secondary-500/20 dark:text-secondary-200' : 'border-gray-200 text-gray-700 hover:bg-gray-50 dark:border-white/10 dark:text-gray-200 dark:hover:bg-white/5' }}"
                            >
                                {{ __('confirmations.yes') }}
                            </button>
                            <button
                                type="button"
                                wire:click="selectAnswer({{ $question->id }}, false)"
                                class="rounded-(--radius-brand) border px-4 py-3 text-sm font-semibold transition duration-150 {{ ($answers[$question->id] ?? null) === false ? 'border-status-rejected bg-status-rejected/10 text-status-rejected' : 'border-gray-200 text-gray-700 hover:bg-gray-50 dark:border-white/10 dark:text-gray-200 dark:hover:bg-white/5' }}"
                            >
                                {{ __('confirmations.no') }}
                            </button>
                        </div>
                        @break
                @endswitch

                @error("answers.{$question->id}")
                    <p class="mt-2 text-xs text-status-rejected">{{ $message }}</p>
                @enderror
            </div>

            <div class="mt-6 flex items-center justify-between gap-3">
                @if ($this->survey?->is_required)
                    <span></span>
                @else
                    <x-ui.button type="button" variant="ghost" size="sm" wire:click="skipSurvey">
                        {{ __('confirmations.skip_button') }}
                    </x-ui.button>
                @endif

                <div class="flex items-center gap-2">
                    @if ($step > 0)
                        <x-ui.button type="button" variant="ghost" wire:click="previousStep">
                            {{ __('confirmations.previous_button') }}
                        </x-ui.button>
                    @endif

                    <x-ui.button type="button" variant="primary" wire:click="nextStep">
                        {{ $step + 1 >= $this->totalSteps ? __('confirmations.submit_button') : __('confirmations.next_button') }}
                    </x-ui.button>
                </div>
            </div>
        </x-ui.card>

    @else
        {{-- 'done' state, and the 'survey' fallback if the survey turned out to have no questions. --}}
        <x-ui.card class="text-center">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-accent-500/10 text-accent-600">
                <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
                </svg>
            </div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('confirmations.done_title') }}</h1>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ __('confirmations.done_description') }}</p>
        </x-ui.card>
    @endif

    <script>
        window.confirmationSignaturePad = function () {
            return {
                drawing: false,
                hasDrawn: false,
                ctx: null,
                last: { x: 0, y: 0 },
                init() {
                    const canvas = this.$refs.pad;
                    const ratio = window.devicePixelRatio || 1;
                    const rect = canvas.getBoundingClientRect();
                    canvas.width = rect.width * ratio;
                    canvas.height = rect.height * ratio;
                    this.ctx = canvas.getContext('2d');
                    this.ctx.scale(ratio, ratio);
                    this.ctx.lineWidth = 2;
                    this.ctx.lineCap = 'round';
                    this.ctx.strokeStyle = '#1C545E';
                },
                pos(e) {
                    const rect = this.$refs.pad.getBoundingClientRect();
                    return { x: e.clientX - rect.left, y: e.clientY - rect.top };
                },
                startDraw(e) {
                    this.drawing = true;
                    this.last = this.pos(e);
                },
                draw(e) {
                    if (! this.drawing) return;
                    const p = this.pos(e);
                    this.ctx.beginPath();
                    this.ctx.moveTo(this.last.x, this.last.y);
                    this.ctx.lineTo(p.x, p.y);
                    this.ctx.stroke();
                    this.last = p;
                    this.hasDrawn = true;
                },
                endDraw() {
                    this.drawing = false;
                },
                clear() {
                    const canvas = this.$refs.pad;
                    this.ctx.clearRect(0, 0, canvas.width, canvas.height);
                    this.hasDrawn = false;
                },
            };
        };
    </script>

    <style>
        .confirmation-checkmark__circle,
        .confirmation-checkmark__check {
            stroke-dasharray: 166;
            stroke-dashoffset: 166;
            animation: confirmation-checkmark-draw 0.6s ease-out forwards;
        }

        .confirmation-checkmark__check {
            animation-delay: 0.4s;
        }

        @keyframes confirmation-checkmark-draw {
            to {
                stroke-dashoffset: 0;
            }
        }
    </style>
</div>

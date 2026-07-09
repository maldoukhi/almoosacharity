@php
    $finalStatus = match ($aid->status) {
        \App\Enums\AidStatus::Approved, \App\Enums\AidStatus::InDisbursement, \App\Enums\AidStatus::Delivered => 'approved',
        \App\Enums\AidStatus::Rejected => 'rejected',
        default => null,
    };
@endphp

<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="font-mono text-2xl font-semibold text-gray-900 dark:text-white">{{ $aid->reference }}</h1>
                <x-ui.badge :color="$aid->status->color()">{{ $aid->status->label() }}</x-ui.badge>
            </div>

            @if ($aid->title)
                <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">{{ $aid->title }}</p>
            @endif

            <p class="mt-1.5 text-sm text-gray-500 dark:text-gray-400">
                <a href="{{ route('admin.beneficiaries.show', $aid->beneficiary) }}" wire:navigate class="text-primary-700 hover:underline dark:text-primary-300">
                    {{ $aid->beneficiary?->full_name }}
                </a>
                <span class="mx-1">&middot;</span>
                {{ $aid->program?->name }}
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if ($this->canDownloadReceipt)
                {{-- Opens the mPDF receipt INLINE in a new tab (no wire:navigate
                     — it's a PDF response, not a Livewire page). --}}
                <x-ui.button href="{{ route('aids.receipt', $aid) }}" target="_blank" variant="ghost">
                    {{ __('aids.receipt.button') }}
                </x-ui.button>
            @endif

            <x-ui.button href="{{ route('aids.index') }}" variant="ghost">
                {{ __('common.back') }}
            </x-ui.button>
        </div>
    </div>

    @if ($this->latestReturnNote)
        <div class="flex items-start gap-3 rounded-(--radius-brand) bg-status-review/10 px-4 py-3 text-sm text-status-review">
            <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
            </svg>
            <div>
                <p class="font-medium">{{ __('aids.returned_notice_title') }}</p>
                <p class="mt-0.5">{{ $this->latestReturnNote }}</p>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <x-ui.card>
                <x-slot:header>
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('aids.details_title') }}</h2>
                </x-slot:header>

                <dl class="grid grid-cols-1 gap-x-6 gap-y-5 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('aids.field_type') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $aid->type->label() }}</dd>
                    </div>

                    @if ($aid->type === \App\Enums\AidType::Cash)
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('aids.field_amount') }}</dt>
                            <dd class="mt-1 text-sm tabular-nums text-gray-900 dark:text-white">{{ number_format((float) $aid->amount, 2) }} {{ __('aids.currency_sar') }}</dd>
                        </div>

                        <div class="sm:col-span-2">
                            <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('aids.field_purpose') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $aid->purpose ?: __('common.dash') }}</dd>
                        </div>
                    @else
                        <div class="sm:col-span-2">
                            <dt class="mb-2 text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('aids.field_items') }}</dt>
                            <dd>
                                <x-ui.table>
                                    <thead>
                                        <tr>
                                            <x-ui.table.th>{{ __('aids.field_item_name') }}</x-ui.table.th>
                                            <x-ui.table.th align="end">{{ __('aids.field_item_quantity') }}</x-ui.table.th>
                                            <x-ui.table.th align="end">{{ __('aids.field_item_estimated_value') }}</x-ui.table.th>
                                            <x-ui.table.th>{{ __('aids.field_item_description') }}</x-ui.table.th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                                        @foreach ($aid->items as $item)
                                            <tr>
                                                <x-ui.table.td class="font-medium text-gray-900 dark:text-white">{{ $item->name }}</x-ui.table.td>
                                                <x-ui.table.td align="end" class="tabular-nums">{{ $item->quantity }}</x-ui.table.td>
                                                <x-ui.table.td align="end" class="tabular-nums">{{ number_format((float) $item->estimated_value, 2) }}</x-ui.table.td>
                                                <x-ui.table.td>{{ $item->description ?: __('common.dash') }}</x-ui.table.td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr class="border-t border-gray-100 dark:border-white/10">
                                            <x-ui.table.td class="font-semibold text-gray-900 dark:text-white">{{ __('aids.items_total') }}</x-ui.table.td>
                                            <x-ui.table.td></x-ui.table.td>
                                            <x-ui.table.td align="end" class="font-semibold tabular-nums text-gray-900 dark:text-white">
                                                {{ number_format((float) $aid->items->sum('estimated_value'), 2) }}
                                            </x-ui.table.td>
                                            <x-ui.table.td></x-ui.table.td>
                                        </tr>
                                    </tfoot>
                                </x-ui.table>
                            </dd>
                        </div>
                    @endif

                    <div class="sm:col-span-2">
                        <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('aids.field_notes') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $aid->notes ?: __('common.dash') }}</dd>
                    </div>

                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('aids.field_created_by') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $aid->createdBy?->name }}</dd>
                    </div>

                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('aids.field_created_at') }}</dt>
                        <dd class="mt-1 text-sm tabular-nums text-gray-900 dark:text-white">{{ $aid->created_at?->translatedFormat('Y/m/d H:i') }}</dd>
                    </div>
                </dl>
            </x-ui.card>

            @if ($aid->isRecurringInstance() || $aid->recurringPlan)
                <x-ui.card>
                    <x-slot:header>
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <h2 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('aids.recurring_series.title') }}</h2>
                            <x-ui.badge color="primary">{{ __('aids.recurring_series.badge') }}</x-ui.badge>
                        </div>
                    </x-slot:header>

                    <div class="space-y-3 text-sm">
                        @if ($aid->isRecurringInstance())
                            <p class="text-gray-700 dark:text-gray-200">{{ __('aids.recurring_series.instance') }}</p>
                            @if ($this->recurringCycle)
                                <p class="text-gray-500 dark:text-gray-400">{{ __('aids.recurring_series.cycle', ['n' => $this->recurringCycle]) }}</p>
                            @endif
                        @elseif ($aid->recurringPlan)
                            <p class="text-gray-700 dark:text-gray-200">{{ __('aids.recurring_series.origin') }}</p>
                        @endif

                        <a href="{{ route('aids.recurring-plans.index') }}" wire:navigate class="inline-flex items-center gap-1 font-medium text-primary-700 hover:underline dark:text-primary-300">
                            {{ __('aids.recurring_series.view_plan') }}
                        </a>
                    </div>
                </x-ui.card>
            @endif

            <x-ui.card>
                <x-slot:header>
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('aids.progress_title') }}</h2>
                </x-slot:header>

                <x-ui.stepper :steps="$this->stages" :current="$this->currentStageIndex" :status="$finalStatus" />
            </x-ui.card>

            <x-ui.card>
                <x-slot:header>
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('aids.timeline_title') }}</h2>
                </x-slot:header>

                @php
                    $timelineItems = collect($this->timeline)->map(fn (array $entry): array => [
                        'title' => $entry['title'],
                        'description' => $entry['meta'] ?? null,
                        'date' => $entry['at'] ?? null,
                        'color' => $entry['color'] ?? 'primary',
                        'icon' => $entry['icon'] ?? 'dot',
                    ]);
                @endphp

                <x-ui.timeline :items="$timelineItems" />
            </x-ui.card>

            @if (in_array($aid->status, [\App\Enums\AidStatus::Approved, \App\Enums\AidStatus::InDisbursement, \App\Enums\AidStatus::Delivered], true))
                <livewire:disbursements.panel :aid="$aid" :wire:key="'disb-'.$aid->id" />
            @endif
        </div>

        <div class="space-y-6">
            <x-ui.card>
                <x-slot:header>
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('aids.actions_title') }}</h2>
                </x-slot:header>

                <div class="space-y-4">
                    @if ($this->canSubmit)
                        <x-ui.button
                            type="button"
                            variant="primary"
                            class="w-full"
                            wire:click="$dispatch('openModal', { component: 'aids.submit-aid-modal', arguments: { aid: '{{ $aid->hashid }}' } })"
                        >
                            {{ __('aids.submit_button') }}
                        </x-ui.button>
                    @endif

                    @if ($this->canAct)
                        <div class="flex flex-col gap-2">
                            @foreach ($this->allowedActions as $action)
                                <x-ui.button
                                    type="button"
                                    variant="{{ match ($action->value) {
                                        'approve' => 'secondary',
                                        'reject' => 'danger',
                                        default => 'ghost',
                                    } }}"
                                    class="w-full"
                                    wire:click="$dispatch('openModal', { component: 'aids.approval-decision-modal', arguments: { aid: '{{ $aid->hashid }}', action: '{{ $action->value }}' } })"
                                >
                                    {{ $action->label() }}
                                </x-ui.button>
                            @endforeach
                        </div>
                    @endif

                    @if ($this->awaitingBeneficiary)
                        <div class="rounded-(--radius-brand) border border-status-review/30 bg-status-review/5 p-4">
                            <div class="flex items-center gap-2">
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-status-review/10 text-status-review">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2m6-2a10 10 0 1 1-20 0 10 10 0 0 1 20 0Z" />
                                    </svg>
                                </span>
                                <div>
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('approvals.beneficiary_response.awaiting_title') }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('approvals.beneficiary_response.awaiting_description') }}</p>
                                </div>
                            </div>

                            @if ($this->stageResponse)
                                <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                                    @if ($this->stageResponse->sent_at)
                                        {{ __('approvals.beneficiary_response.sent_at', ['at' => $this->stageResponse->sent_at->translatedFormat('Y/m/d H:i')]) }}
                                    @else
                                        {{ __('approvals.beneficiary_response.not_sent') }}
                                    @endif
                                </p>
                            @endif

                            <x-ui.button
                                type="button"
                                variant="secondary"
                                class="mt-4 w-full"
                                data-confirm="{{ __('approvals.beneficiary_response.confirm_resend') }}"
                                x-on:click="uiConfirm($el.dataset.confirm, () => $wire.resendBeneficiaryLink())"
                                wire:target="resendBeneficiaryLink"
                                wire:loading.attr="disabled"
                            >
                                {{ __('approvals.beneficiary_response.resend_button') }}
                            </x-ui.button>
                        </div>
                    @endif

                    @if ($this->canCancel)
                        <x-ui.button
                            type="button"
                            variant="ghost"
                            class="w-full"
                            wire:click="$dispatch('openModal', { component: 'aids.cancel-aid-modal', arguments: { aid: '{{ $aid->hashid }}' } })"
                        >
                            {{ __('common.cancel') }}
                        </x-ui.button>
                    @endif

                    @if (! $this->canSubmit && ! $this->canAct && ! $this->awaitingBeneficiary && ! $this->canCancel)
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('aids.no_actions_available') }}</p>
                    @endif
                </div>
            </x-ui.card>

            @if (in_array($aid->status, [\App\Enums\AidStatus::Delivered, \App\Enums\AidStatus::Confirmed], true))
                <x-ui.card>
                    <x-slot:header>
                        <h2 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('confirmations.tracking_title') }}</h2>
                    </x-slot:header>

                    @if ($this->confirmation)
                        <ul class="space-y-3 text-sm">
                            <li class="flex items-center justify-between gap-3">
                                <span class="text-gray-500 dark:text-gray-400">{{ __('confirmations.tracking_sent_at') }}</span>
                                @if ($this->confirmation->sent_at)
                                    <span class="font-medium tabular-nums text-gray-900 dark:text-white">{{ $this->confirmation->sent_at->translatedFormat('Y/m/d H:i') }}</span>
                                @else
                                    <span class="text-gray-400 dark:text-gray-500">{{ __('confirmations.tracking_not_sent') }}</span>
                                @endif
                            </li>

                            <li class="flex items-center justify-between gap-3">
                                <span class="text-gray-500 dark:text-gray-400">{{ __('confirmations.tracking_opened_at') }}</span>
                                @if ($this->confirmation->opened_at)
                                    <span class="font-medium tabular-nums text-gray-900 dark:text-white">{{ $this->confirmation->opened_at->translatedFormat('Y/m/d H:i') }}</span>
                                @else
                                    <span class="text-gray-400 dark:text-gray-500">{{ __('confirmations.tracking_not_opened') }}</span>
                                @endif
                            </li>

                            <li class="flex items-center justify-between gap-3">
                                <span class="text-gray-500 dark:text-gray-400">{{ __('confirmations.tracking_confirmed_at') }}</span>
                                @if ($this->confirmation->confirmed_at)
                                    <x-ui.badge color="delivered">{{ $this->confirmation->confirmed_at->translatedFormat('Y/m/d H:i') }}</x-ui.badge>
                                @else
                                    <span class="text-gray-400 dark:text-gray-500">{{ __('confirmations.tracking_not_confirmed') }}</span>
                                @endif
                            </li>

                            @if ($this->confirmation->confirmed_ip)
                                <li class="text-xs text-gray-400 dark:text-gray-500">
                                    {{ __('confirmations.tracking_confirmed_ip', ['ip' => $this->confirmation->confirmed_ip]) }}
                                </li>
                            @endif

                            @if ($this->confirmation->reminder_sent_at)
                                <li class="text-xs text-gray-400 dark:text-gray-500">
                                    {{ __('confirmations.tracking_reminder_sent_at') }} &middot; {{ $this->confirmation->reminder_sent_at->translatedFormat('Y/m/d H:i') }}
                                </li>
                            @endif
                        </ul>

                        @if ($this->canResendConfirmation && ! $this->confirmation->isConfirmed())
                            <x-ui.button
                                type="button"
                                variant="ghost"
                                class="mt-4 w-full"
                                data-confirm="{{ __('confirmations.confirm_resend') }}"
                                x-on:click="uiConfirm($el.dataset.confirm, () => $wire.resendConfirmation())"
                            >
                                {{ __('confirmations.resend_button') }}
                            </x-ui.button>
                        @endif
                    @else
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('confirmations.tracking_no_confirmation_yet') }}</p>
                    @endif
                </x-ui.card>
            @endif

            @if ($this->showsSurveyCard)
                <x-ui.card>
                    <x-slot:header>
                        <h2 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('surveys.aid_detail.title') }}</h2>
                    </x-slot:header>

                    @if ($this->surveyResponse)
                        <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">
                            {{ __('surveys.aid_detail.subtitle', ['survey' => $this->surveyResponse->survey?->title]) }}
                            @if ($this->surveyResponse->submitted_at)
                                <span class="mx-1">&middot;</span>
                                <span class="tabular-nums">{{ __('surveys.aid_detail.submitted_at', ['date' => $this->surveyResponse->submitted_at->translatedFormat('Y/m/d')]) }}</span>
                            @endif
                        </p>

                        <div class="space-y-4">
                            @foreach ($this->surveyDetail as $item)
                                <div wire:key="aid-survey-answer-{{ $item['id'] }}" class="rounded-(--radius-brand) border border-gray-100 bg-gray-50 px-3.5 py-3 dark:border-white/10 dark:bg-white/5">
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
                    @else
                        <x-ui.empty-state
                            :title="__('surveys.aid_detail.empty_title')"
                            :description="__('surveys.aid_detail.empty_description')"
                        />
                    @endif
                </x-ui.card>
            @endif
        </div>
    </div>
</div>

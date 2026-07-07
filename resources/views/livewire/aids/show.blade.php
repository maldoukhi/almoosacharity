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

            <p class="mt-1.5 text-sm text-gray-500 dark:text-gray-400">
                <a href="{{ route('admin.beneficiaries.show', $aid->beneficiary) }}" wire:navigate class="text-primary-700 hover:underline dark:text-primary-300">
                    {{ $aid->beneficiary?->full_name }}
                </a>
                <span class="mx-1">&middot;</span>
                {{ $aid->program?->name }}
            </p>
        </div>

        <x-ui.button href="{{ route('aids.index') }}" variant="ghost">
            {{ __('common.back') }}
        </x-ui.button>
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

            <x-ui.card>
                <x-slot:header>
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('aids.progress_title') }}</h2>
                </x-slot:header>

                <x-ui.stepper :steps="$this->stages" :current="$this->currentStageIndex" :status="$finalStatus" />
            </x-ui.card>

            <x-ui.card>
                <x-slot:header>
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('aids.decisions_title') }}</h2>
                </x-slot:header>

                <x-ui.timeline :items="$this->timeline" />
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
                            wire:click="submit"
                            wire:confirm="{{ __('aids.confirm_submit') }}"
                        >
                            {{ __('aids.submit_button') }}
                        </x-ui.button>
                    @endif

                    @if ($this->canAct)
                        <div class="space-y-3">
                            <div>
                                <label for="decisionNote" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-200">
                                    {{ __('aids.field_decision_note') }}
                                </label>
                                <textarea
                                    id="decisionNote"
                                    wire:model="decisionNote"
                                    rows="3"
                                    class="block w-full rounded-(--radius-brand) border border-gray-300 bg-white px-3.5 py-2.5 text-sm text-gray-900 shadow-sm transition duration-200 ease-out focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30 dark:border-white/10 dark:bg-primary-950/30 dark:text-gray-100"
                                ></textarea>
                                @error('decisionNote')
                                    <p class="mt-1.5 text-xs text-status-rejected">{{ $message }}</p>
                                @enderror
                                <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">{{ __('aids.decision_note_hint') }}</p>
                            </div>

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
                                        wire:click="decide('{{ $action->value }}')"
                                        wire:confirm="{{ __('aids.confirm_decision') }}"
                                    >
                                        {{ $action->label() }}
                                    </x-ui.button>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if ($this->canCancel)
                        <x-ui.button
                            type="button"
                            variant="ghost"
                            class="w-full"
                            wire:click="cancel"
                            wire:confirm="{{ __('aids.confirm_cancel') }}"
                        >
                            {{ __('common.cancel') }}
                        </x-ui.button>
                    @endif

                    @if (! $this->canSubmit && ! $this->canAct && ! $this->canCancel)
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
                                wire:click="resendConfirmation"
                                wire:confirm="{{ __('confirmations.confirm_resend') }}"
                            >
                                {{ __('confirmations.resend_button') }}
                            </x-ui.button>
                        @endif
                    @else
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('confirmations.tracking_no_confirmation_yet') }}</p>
                    @endif
                </x-ui.card>
            @endif
        </div>
    </div>
</div>

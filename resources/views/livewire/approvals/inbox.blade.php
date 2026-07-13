@php
    $programOptions = $this->programs->mapWithKeys(fn ($program) => [$program->id => $program->name]);
@endphp

<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ __('approvals.inbox_title') }}</h1>
                <x-ui.badge color="review">{{ $this->pendingCount }}</x-ui.badge>
            </div>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('approvals.inbox_subtitle') }}</p>
        </div>

        <x-ui.help-link section="approvals" />
    </div>

    <div class="inline-flex rounded-lg bg-gray-100 p-1 dark:bg-white/5" role="tablist">
        <button
            type="button"
            role="tab"
            :aria-selected="@js($this->view === 'pending')"
            wire:click="$set('view', 'pending')"
            @class([
                'rounded-md px-4 py-2 text-sm font-medium transition',
                'bg-white text-primary-700 shadow-sm dark:bg-white/10 dark:text-white' => $this->view === 'pending',
                'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' => $this->view !== 'pending',
            ])
        >
            {{ __('approvals.tab_pending') }}
        </button>
        <button
            type="button"
            role="tab"
            :aria-selected="@js($this->view === 'history')"
            wire:click="$set('view', 'history')"
            @class([
                'rounded-md px-4 py-2 text-sm font-medium transition',
                'bg-white text-primary-700 shadow-sm dark:bg-white/10 dark:text-white' => $this->view === 'history',
                'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' => $this->view !== 'history',
            ])
        >
            {{ __('approvals.tab_history') }}
        </button>
    </div>

    <x-ui.card>
        <div class="grid grid-cols-1 gap-4 sm:max-w-xs">
            <x-ui.select
                :label="__('approvals.filter_program')"
                name="programFilter"
                wire:model.live="programFilter"
                :placeholder="__('common.all')"
                :options="$programOptions"
            />
        </div>
    </x-ui.card>

    @if ($this->view === 'pending')
    <div class="relative">
        <div wire:loading.flex wire:target="programFilter" class="hidden flex-col gap-2" style="display: none">
            <x-ui.skeleton height="3rem" />
            <x-ui.skeleton height="3rem" />
            <x-ui.skeleton height="3rem" />
        </div>

        <div wire:loading.remove wire:target="programFilter">
            @if ($this->aids->isEmpty())
                <x-ui.empty-state :title="__('approvals.empty_title')" :description="__('approvals.empty_description')">
                    <x-slot:icon>
                        <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                    </x-slot:icon>
                </x-ui.empty-state>
            @else
                <x-ui.table>
                    <thead>
                        <tr>
                            <x-ui.table.th>{{ __('aids.field_reference') }}</x-ui.table.th>
                            <x-ui.table.th>{{ __('aids.field_beneficiary') }}</x-ui.table.th>
                            <x-ui.table.th>{{ __('aids.field_program') }}</x-ui.table.th>
                            <x-ui.table.th align="end">{{ __('aids.field_amount') }}</x-ui.table.th>
                            <x-ui.table.th>{{ __('aids.field_current_stage') }}</x-ui.table.th>
                            <x-ui.table.th>{{ __('approvals.field_waiting_since') }}</x-ui.table.th>
                            <x-ui.table.th align="end">{{ __('common.actions') }}</x-ui.table.th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                        @foreach ($this->aids as $aid)
                            @php
                                $waitingDays = $aid->submitted_at?->diffInDays(now());
                                $overdueDays = $this->overdueDays($aid);
                            @endphp
                            <tr class="transition duration-150 hover:bg-gray-50 dark:hover:bg-white/5">
                                <x-ui.table.td class="font-mono font-medium text-gray-900 dark:text-white">{{ $aid->reference }}</x-ui.table.td>
                                <x-ui.table.td>{{ $aid->beneficiary?->full_name }}</x-ui.table.td>
                                <x-ui.table.td>{{ $aid->program?->name }}</x-ui.table.td>
                                <x-ui.table.td align="end" class="tabular-nums">
                                    @if ($aid->type === \App\Enums\AidType::Cash)
                                        {{ number_format((float) $aid->amount, 2) }} {{ __('aids.currency_sar') }}
                                    @else
                                        <x-ui.badge color="accent">{{ $aid->type->label() }}</x-ui.badge>
                                    @endif
                                </x-ui.table.td>
                                <x-ui.table.td>
                                    <div class="flex flex-col gap-1">
                                        <span class="text-status-review">{{ $aid->currentStage?->name }}</span>

                                        @if ($overdueDays !== null)
                                            <span class="inline-flex w-fit items-center gap-1 rounded-full bg-status-rejected/10 px-2 py-0.5 text-xs font-medium text-status-rejected">
                                                <svg class="h-3 w-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                                                </svg>
                                                {{ __('approvals.overdue_badge', ['days' => $overdueDays]) }}
                                            </span>
                                        @endif
                                    </div>
                                </x-ui.table.td>
                                <x-ui.table.td>
                                    <span @class([
                                        'tabular-nums',
                                        'font-medium text-status-rejected' => $waitingDays !== null && $waitingDays > 3,
                                        'text-gray-500 dark:text-gray-400' => $waitingDays === null || $waitingDays <= 3,
                                    ])>
                                        {{ $aid->submitted_at?->diffForHumans() }}
                                    </span>
                                </x-ui.table.td>
                                <x-ui.table.td align="end">
                                    <x-ui.button href="{{ route('aids.show', $aid) }}" variant="primary" size="sm">
                                        {{ __('approvals.review_button') }}
                                    </x-ui.button>
                                </x-ui.table.td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-ui.table>

                <div class="mt-4">
                    {{ $this->aids->links() }}
                </div>
            @endif
        </div>
    </div>
    @else
    <div class="relative">
        <div wire:loading.flex wire:target="programFilter,view" class="hidden flex-col gap-2" style="display: none">
            <x-ui.skeleton height="3rem" />
            <x-ui.skeleton height="3rem" />
            <x-ui.skeleton height="3rem" />
        </div>

        <div wire:loading.remove wire:target="programFilter,view">
            @if ($this->decisions->isEmpty())
                <x-ui.empty-state :title="__('approvals.history_empty')" :description="__('approvals.history_empty_description')">
                    <x-slot:icon>
                        <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    </x-slot:icon>
                </x-ui.empty-state>
            @else
                <x-ui.table>
                    <thead>
                        <tr>
                            <x-ui.table.th>{{ __('aids.field_reference') }}</x-ui.table.th>
                            <x-ui.table.th>{{ __('aids.field_title') }}</x-ui.table.th>
                            <x-ui.table.th>{{ __('aids.field_beneficiary') }}</x-ui.table.th>
                            <x-ui.table.th>{{ __('aids.field_program') }}</x-ui.table.th>
                            <x-ui.table.th>{{ __('approvals.col_action') }}</x-ui.table.th>
                            <x-ui.table.th>{{ __('approvals.col_note') }}</x-ui.table.th>
                            <x-ui.table.th>{{ __('approvals.col_decided_at') }}</x-ui.table.th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                        @foreach ($this->decisions as $decision)
                            @php
                                $actionColor = match ($decision->action) {
                                    \App\Enums\ApprovalAction::Approve => 'approved',
                                    \App\Enums\ApprovalAction::Reject => 'rejected',
                                    \App\Enums\ApprovalAction::Return => 'review',
                                    default => 'gray',
                                };
                            @endphp
                            <tr wire:key="decision-{{ $decision->id }}" class="transition duration-150 hover:bg-gray-50 dark:hover:bg-white/5">
                                <x-ui.table.td class="font-mono font-medium">
                                    @if ($decision->aid)
                                        <a href="{{ route('aids.show', $decision->aid) }}" class="text-primary-700 hover:underline dark:text-primary-300">
                                            {{ $decision->aid->reference }}
                                        </a>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </x-ui.table.td>
                                <x-ui.table.td>{{ $decision->aid?->display_title ?? '—' }}</x-ui.table.td>
                                <x-ui.table.td>{{ $decision->aid?->beneficiary?->full_name ?? '—' }}</x-ui.table.td>
                                <x-ui.table.td>{{ $decision->aid?->program?->name ?? '—' }}</x-ui.table.td>
                                <x-ui.table.td>
                                    <x-ui.badge :color="$actionColor">{{ $decision->action->label() }}</x-ui.badge>
                                </x-ui.table.td>
                                <x-ui.table.td class="max-w-xs">
                                    <span class="block truncate text-gray-600 dark:text-gray-300" title="{{ $decision->note }}">
                                        {{ $decision->note ?: '—' }}
                                    </span>
                                </x-ui.table.td>
                                <x-ui.table.td>
                                    <span class="tabular-nums text-gray-500 dark:text-gray-400">
                                        {{ $decision->decided_at?->translatedFormat('d MMM yyyy — HH:mm') }}
                                    </span>
                                </x-ui.table.td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-ui.table>

                <div class="mt-4">
                    {{ $this->decisions->links() }}
                </div>
            @endif
        </div>
    </div>
    @endif
</div>

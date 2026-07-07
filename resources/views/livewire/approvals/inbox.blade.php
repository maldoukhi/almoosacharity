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
                                    <span class="text-status-review">{{ $aid->currentStage?->name }}</span>
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
</div>

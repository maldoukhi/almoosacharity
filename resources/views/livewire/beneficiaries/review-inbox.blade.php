<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ __('beneficiaries.flow.inbox_title') }}</h1>
                <x-ui.badge color="review">{{ $this->pendingCount }}</x-ui.badge>
            </div>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('beneficiaries.flow.inbox_subtitle') }}</p>
        </div>

        <x-ui.help-link section="beneficiaries" />
    </div>

    @if ($this->beneficiaries->isEmpty())
        <x-ui.empty-state :title="__('beneficiaries.flow.inbox_empty_title')" :description="__('beneficiaries.flow.inbox_empty_description')">
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
                    <x-ui.table.th>{{ __('beneficiaries.field_full_name') }}</x-ui.table.th>
                    <x-ui.table.th>{{ __('beneficiaries.field_city') }}</x-ui.table.th>
                    <x-ui.table.th>{{ __('beneficiaries.flow.field_current_stage') }}</x-ui.table.th>
                    <x-ui.table.th>{{ __('beneficiaries.flow.field_waiting_since') }}</x-ui.table.th>
                    <x-ui.table.th align="end">{{ __('common.actions') }}</x-ui.table.th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                @foreach ($this->beneficiaries as $beneficiary)
                    @php
                        $waitingDays = $beneficiary->submitted_at?->diffInDays(now());
                    @endphp
                    <tr wire:key="review-bene-{{ $beneficiary->id }}" class="transition duration-150 hover:bg-gray-50 dark:hover:bg-white/5">
                        <x-ui.table.td class="font-medium text-gray-900 dark:text-white">{{ $beneficiary->full_name }}</x-ui.table.td>
                        <x-ui.table.td>{{ $beneficiary->city }}</x-ui.table.td>
                        <x-ui.table.td>
                            <span class="text-status-review">{{ $beneficiary->currentStage?->name }}</span>
                        </x-ui.table.td>
                        <x-ui.table.td>
                            <span @class([
                                'tabular-nums',
                                'font-medium text-status-rejected' => $waitingDays !== null && $waitingDays > 3,
                                'text-gray-500 dark:text-gray-400' => $waitingDays === null || $waitingDays <= 3,
                            ])>
                                {{ $beneficiary->submitted_at?->diffForHumans() }}
                            </span>
                        </x-ui.table.td>
                        <x-ui.table.td align="end">
                            <x-ui.button href="{{ route('admin.beneficiaries.show', $beneficiary) }}" variant="primary" size="sm">
                                {{ __('beneficiaries.flow.review_button') }}
                            </x-ui.button>
                        </x-ui.table.td>
                    </tr>
                @endforeach
            </tbody>
        </x-ui.table>

        <div class="mt-4">
            {{ $this->beneficiaries->links() }}
        </div>
    @endif
</div>

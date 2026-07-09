<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">
            {{ __('nav.welcome', ['name' => auth()->user()->name]) }}
        </h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('nav.dashboard_subtitle') }}</p>
    </div>

    {{-- Operational "pending indicators": stale/expiring items needing follow-up now. --}}
    <div>
        <h2 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('dashboard.ops.title') }}</h2>
        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ __('dashboard.ops.subtitle') }}</p>

        <div class="mt-3 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
            @can('approvals.view')
                <a
                    href="{{ route('approvals.inbox') }}"
                    wire:navigate
                    wire:key="ops-overdue-approvals"
                    class="block rounded-(--radius-brand) transition duration-150 ease-out hover:-translate-y-0.5"
                >
                    <div class="flex items-start gap-4 rounded-(--radius-brand) bg-white p-5 shadow-(--shadow-card) dark:bg-primary-950/40 dark:ring-1 dark:ring-white/5">
                        <div @class([
                            'flex h-12 w-12 shrink-0 items-center justify-center rounded-full',
                            'bg-status-rejected/10 text-status-rejected dark:bg-status-rejected dark:text-white' => $this->overdueApprovalsCount > 0,
                            'bg-gray-100 text-gray-500 dark:bg-white/10 dark:text-gray-400' => $this->overdueApprovalsCount === 0,
                        ])>
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p @class([
                                'text-2xl font-semibold tabular-nums',
                                'text-status-rejected' => $this->overdueApprovalsCount > 0,
                                'text-gray-900 dark:text-white' => $this->overdueApprovalsCount === 0,
                            ])>{{ $this->overdueApprovalsCount }}</p>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ __('dashboard.ops.overdue_approvals_label') }}</p>
                            <p class="mt-0.5 truncate text-xs text-gray-500 dark:text-gray-400">{{ __('dashboard.ops.overdue_approvals_description') }}</p>
                        </div>
                    </div>
                </a>
            @endcan

            @can('aids.view')
                <a
                    href="{{ route('aids.index') }}"
                    wire:navigate
                    wire:key="ops-expired-confirmations"
                    class="block rounded-(--radius-brand) transition duration-150 ease-out hover:-translate-y-0.5"
                >
                    <div class="flex items-start gap-4 rounded-(--radius-brand) bg-white p-5 shadow-(--shadow-card) dark:bg-primary-950/40 dark:ring-1 dark:ring-white/5">
                        <div @class([
                            'flex h-12 w-12 shrink-0 items-center justify-center rounded-full',
                            'bg-status-rejected/10 text-status-rejected dark:bg-status-rejected dark:text-white' => $this->expiredConfirmationsCount > 0,
                            'bg-gray-100 text-gray-500 dark:bg-white/10 dark:text-gray-400' => $this->expiredConfirmationsCount === 0,
                        ])>
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                            </svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p @class([
                                'text-2xl font-semibold tabular-nums',
                                'text-status-rejected' => $this->expiredConfirmationsCount > 0,
                                'text-gray-900 dark:text-white' => $this->expiredConfirmationsCount === 0,
                            ])>{{ $this->expiredConfirmationsCount }}</p>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ __('dashboard.ops.expired_confirmations_label') }}</p>
                            <p class="mt-0.5 truncate text-xs text-gray-500 dark:text-gray-400">{{ __('dashboard.ops.expired_confirmations_description') }}</p>
                        </div>
                    </div>
                </a>
            @endcan

            @can('aids.view')
                <a
                    href="{{ route('aids.recurring-plans.index') }}"
                    wire:navigate
                    wire:key="ops-upcoming-recurring"
                    class="block rounded-(--radius-brand) transition duration-150 ease-out hover:-translate-y-0.5"
                >
                    <div class="flex items-start gap-4 rounded-(--radius-brand) bg-white p-5 shadow-(--shadow-card) dark:bg-primary-950/40 dark:ring-1 dark:ring-white/5">
                        <div @class([
                            'flex h-12 w-12 shrink-0 items-center justify-center rounded-full',
                            'bg-status-review/10 text-status-review dark:bg-status-review dark:text-white' => $this->upcomingRecurringPlansCount > 0,
                            'bg-gray-100 text-gray-500 dark:bg-white/10 dark:text-gray-400' => $this->upcomingRecurringPlansCount === 0,
                        ])>
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                            </svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p @class([
                                'text-2xl font-semibold tabular-nums',
                                'text-status-review' => $this->upcomingRecurringPlansCount > 0,
                                'text-gray-900 dark:text-white' => $this->upcomingRecurringPlansCount === 0,
                            ])>{{ $this->upcomingRecurringPlansCount }}</p>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ __('dashboard.ops.upcoming_recurring_label') }}</p>
                            <p class="mt-0.5 truncate text-xs text-gray-500 dark:text-gray-400">{{ __('dashboard.ops.upcoming_recurring_description') }}</p>
                        </div>
                    </div>
                </a>
            @endcan

            @can('beneficiaries.view')
                <a
                    href="{{ route('admin.beneficiaries.index') }}"
                    wire:navigate
                    wire:key="ops-overdue-beneficiary-reviews"
                    class="block rounded-(--radius-brand) transition duration-150 ease-out hover:-translate-y-0.5"
                >
                    <div class="flex items-start gap-4 rounded-(--radius-brand) bg-white p-5 shadow-(--shadow-card) dark:bg-primary-950/40 dark:ring-1 dark:ring-white/5">
                        <div @class([
                            'flex h-12 w-12 shrink-0 items-center justify-center rounded-full',
                            'bg-status-rejected/10 text-status-rejected dark:bg-status-rejected dark:text-white' => $this->overdueBeneficiaryReviewsCount > 0,
                            'bg-gray-100 text-gray-500 dark:bg-white/10 dark:text-gray-400' => $this->overdueBeneficiaryReviewsCount === 0,
                        ])>
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                            </svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p @class([
                                'text-2xl font-semibold tabular-nums',
                                'text-status-rejected' => $this->overdueBeneficiaryReviewsCount > 0,
                                'text-gray-900 dark:text-white' => $this->overdueBeneficiaryReviewsCount === 0,
                            ])>{{ $this->overdueBeneficiaryReviewsCount }}</p>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ __('dashboard.ops.overdue_beneficiary_reviews_label') }}</p>
                            <p class="mt-0.5 truncate text-xs text-gray-500 dark:text-gray-400">{{ __('dashboard.ops.overdue_beneficiary_reviews_description') }}</p>
                        </div>
                    </div>
                </a>
            @endcan
        </div>
    </div>

    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        @can('beneficiaries.view')
            <x-ui.stat-card :label="__('ui.stat_beneficiaries')" :value="$this->beneficiariesCount">
                <x-slot:icon>
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 9.75 12 6l3 3.75M9 14.25 12 18l3-3.75" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 4.5h15v15h-15z" />
                    </svg>
                </x-slot:icon>
            </x-ui.stat-card>
        @endcan

        @can('aids.view')
            <x-ui.stat-card :label="__('ui.stat_aids')" :value="$this->aidsCount">
                <x-slot:icon>
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-6-6h12" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75h16.5v16.5H3.75z" />
                    </svg>
                </x-slot:icon>
            </x-ui.stat-card>

            <x-ui.stat-card :label="__('reports.dashboard.stat_approved_this_month')" :value="$this->approvedThisMonth">
                <x-slot:icon>
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </x-slot:icon>
            </x-ui.stat-card>
        @endcan

        @can('approvals.view')
            <a href="{{ route('approvals.inbox') }}" wire:navigate class="block rounded-(--radius-brand) transition duration-150 ease-out hover:-translate-y-0.5">
                <x-ui.stat-card
                    :label="__('reports.dashboard.stat_pending_inbox')"
                    :value="$this->pendingForMyRole"
                    :trend="__('reports.dashboard.stat_pending_inbox_link')"
                />
            </a>
        @endcan
    </div>

    {{-- Aids the beneficiary reported as partially or not received --}}
    @can('aids.view')
    <x-ui.card>
        <x-slot:header>
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('reports.dashboard.receipt_issues_title') }}</h2>
                    @if ($this->receiptIssuesCount > 0)
                        <x-ui.badge color="rejected">{{ $this->receiptIssuesCount }}</x-ui.badge>
                    @endif
                </div>
                @if ($this->receiptIssuesCount > 0)
                    <a href="{{ route('aids.index') }}" wire:navigate class="text-xs font-medium text-primary-700 hover:underline dark:text-primary-300">
                        {{ __('reports.dashboard.receipt_issues_view_all') }}
                    </a>
                @endif
            </div>
        </x-slot:header>

        @if ($this->receiptIssues->isEmpty())
            <p class="py-2 text-sm text-gray-500 dark:text-gray-400">{{ __('reports.dashboard.receipt_issues_empty') }}</p>
        @else
            <ul class="divide-y divide-gray-100 dark:divide-white/10">
                @foreach ($this->receiptIssues as $issue)
                    <li wire:key="receipt-issue-{{ $issue->id }}" class="flex items-center justify-between gap-3 py-3">
                        <div class="min-w-0">
                            <a href="{{ route('aids.show', $issue) }}" wire:navigate class="font-mono text-sm font-medium text-primary-700 hover:underline dark:text-primary-300">
                                {{ $issue->reference }}
                            </a>
                            <p class="truncate text-xs text-gray-500 dark:text-gray-400">
                                {{ $issue->beneficiary?->full_name }}@if ($issue->program) · {{ $issue->program->name }}@endif
                            </p>
                        </div>
                        <x-ui.badge :color="$issue->confirmation->receipt_status->color()">
                            {{ $issue->confirmation->receipt_status->label() }}
                        </x-ui.badge>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-ui.card>

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
        <x-ui.card>
            <x-slot:header>
                <h2 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('reports.dashboard.chart_by_status') }}</h2>
            </x-slot:header>

            <x-ui.chart
                type="donut"
                :series="$this->aidsByStatus['series']"
                :labels="$this->aidsByStatus['labels']"
                :colors="$this->aidsByStatus['colors']"
                :height="300"
            />
        </x-ui.card>

        <x-ui.card>
            <x-slot:header>
                <h2 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('reports.dashboard.chart_by_type') }}</h2>
            </x-slot:header>

            <x-ui.chart
                type="donut"
                :series="$this->aidsByType['series']"
                :labels="$this->aidsByType['labels']"
                :colors="$this->aidsByType['colors']"
                :height="300"
            />
        </x-ui.card>
    </div>

    <x-ui.card>
        <x-slot:header>
            <h2 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('reports.dashboard.chart_by_month') }}</h2>
        </x-slot:header>

        <x-ui.chart
            type="line"
            :series="[
                ['name' => __('reports.dashboard.chart_by_month_count'), 'type' => 'column', 'data' => $this->aidsByMonth['counts']],
                ['name' => __('reports.dashboard.chart_by_month_cash'), 'type' => 'line', 'data' => $this->aidsByMonth['cashSums']],
            ]"
            :labels="$this->aidsByMonth['labels']"
            :colors="['#1C545E', '#85BF40']"
            :height="320"
        />
    </x-ui.card>
    @endcan
</div>

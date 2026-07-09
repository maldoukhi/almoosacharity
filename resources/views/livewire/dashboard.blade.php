<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">
            {{ __('nav.welcome', ['name' => auth()->user()->name]) }}
        </h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('nav.dashboard_subtitle') }}</p>
    </div>

    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <x-ui.stat-card :label="__('ui.stat_beneficiaries')" :value="$this->beneficiariesCount">
            <x-slot:icon>
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 9.75 12 6l3 3.75M9 14.25 12 18l3-3.75" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 4.5h15v15h-15z" />
                </svg>
            </x-slot:icon>
        </x-ui.stat-card>

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
</div>

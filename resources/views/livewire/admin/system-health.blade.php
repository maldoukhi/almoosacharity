<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ __('health.title') }}</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('health.subtitle') }}</p>
    </div>

    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
        {{-- 1. Queue: pending + failed jobs. --}}
        <x-ui.card wire:key="health-queue">
            <div class="flex items-start justify-between gap-3">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('health.queue.label') }}</p>
                <span class="mt-1 inline-flex h-2.5 w-2.5 shrink-0 rounded-full {{ $this->stateDotClass($this->queueStatus['state']) }}" aria-hidden="true"></span>
            </div>

            @if ($this->queueStatus['pending'] === null)
                <p class="mt-3 text-sm text-gray-400 dark:text-gray-500">{{ __('health.unavailable') }}</p>
                <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">{{ __('health.queue.unavailable_hint') }}</p>
            @else
                <div class="mt-3 flex items-end gap-6">
                    <div>
                        <p class="text-2xl font-semibold tabular-nums text-gray-900 dark:text-white">{{ $this->queueStatus['pending'] }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('health.queue.pending') }}</p>
                    </div>
                    <div>
                        <p @class([
                            'text-2xl font-semibold tabular-nums',
                            'text-status-rejected' => $this->queueStatus['failed'] > 0,
                            'text-gray-900 dark:text-white' => $this->queueStatus['failed'] === 0,
                        ])>{{ $this->queueStatus['failed'] }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('health.queue.failed') }}</p>
                    </div>
                </div>
            @endif
        </x-ui.card>

        {{-- 2. Failed messages in the last 24h. --}}
        <x-ui.card wire:key="health-messages">
            <div class="flex items-start justify-between gap-3">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('health.messages.label') }}</p>
                <span class="mt-1 inline-flex h-2.5 w-2.5 shrink-0 rounded-full {{ $this->stateDotClass($this->messageFailureStatus['state']) }}" aria-hidden="true"></span>
            </div>

            @if ($this->messageFailureStatus['count'] === null)
                <p class="mt-3 text-sm text-gray-400 dark:text-gray-500">{{ __('health.unavailable') }}</p>
            @else
                <p @class([
                    'mt-3 text-2xl font-semibold tabular-nums',
                    'text-status-rejected' => $this->messageFailureStatus['count'] > 0,
                    'text-gray-900 dark:text-white' => $this->messageFailureStatus['count'] === 0,
                ])>{{ $this->messageFailureStatus['count'] }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('health.messages.description') }}</p>
            @endif

            @can('reports.view')
                <a
                    href="{{ route('reports.messages') }}"
                    wire:navigate
                    class="mt-3 inline-block text-xs font-medium text-primary hover:underline dark:text-primary-300"
                >
                    {{ __('health.messages.view_report') }}
                </a>
            @endcan
        </x-ui.card>

        {{-- 3. Latest recurring-aid generation + overdue plan count. --}}
        <x-ui.card wire:key="health-recurring">
            <div class="flex items-start justify-between gap-3">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('health.recurring.label') }}</p>
                <span class="mt-1 inline-flex h-2.5 w-2.5 shrink-0 rounded-full {{ $this->stateDotClass($this->recurringStatus['state']) }}" aria-hidden="true"></span>
            </div>

            @if ($this->recurringStatus['lastGeneratedAt'] === null && $this->recurringStatus['overdueCount'] === null)
                <p class="mt-3 text-sm text-gray-400 dark:text-gray-500">{{ __('health.unavailable') }}</p>
            @else
                <p class="mt-3 text-sm font-semibold text-gray-900 dark:text-white">
                    {{ $this->recurringStatus['lastGeneratedAt'] === null ? __('health.recurring.never_run') : __('health.recurring.last_generated_at', ['date' => $this->recurringStatus['lastGeneratedAt']]) }}
                </p>
                <p @class([
                    'mt-1 text-xs',
                    'text-status-rejected' => $this->recurringStatus['overdueCount'] > 0,
                    'text-gray-500 dark:text-gray-400' => $this->recurringStatus['overdueCount'] === 0,
                ])>
                    {{ $this->recurringStatus['overdueCount'] > 0 ? __('health.recurring.overdue', ['count' => $this->recurringStatus['overdueCount']]) : __('health.recurring.no_overdue') }}
                </p>
            @endif
        </x-ui.card>

        {{-- 4. Newest backup file: timestamp + size. --}}
        <x-ui.card wire:key="health-backup">
            <div class="flex items-start justify-between gap-3">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('health.backup.label') }}</p>
                <span class="mt-1 inline-flex h-2.5 w-2.5 shrink-0 rounded-full {{ $this->stateDotClass($this->backupStatus['state']) }}" aria-hidden="true"></span>
            </div>

            @if ($this->backupStatus['empty'])
                <p class="mt-3 text-sm text-gray-400 dark:text-gray-500">{{ __('health.backup.empty') }}</p>
            @elseif ($this->backupStatus['newestAt'] === null)
                <p class="mt-3 text-sm text-gray-400 dark:text-gray-500">{{ __('health.unavailable') }}</p>
            @else
                <p class="mt-3 text-sm font-semibold text-gray-900 dark:text-white">{{ __('health.backup.newest_at', ['date' => $this->backupStatus['newestAt']]) }}</p>
                @if ($this->backupStatus['sizeBytes'] !== null)
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('health.backup.size', ['size' => $this->formatBytes($this->backupStatus['sizeBytes'])]) }}</p>
                @endif
                @if ($this->backupStatus['state'] === 'error')
                    <p class="mt-1 text-xs text-status-rejected">{{ __('health.backup.stale_hint') }}</p>
                @endif
            @endif
        </x-ui.card>

        {{-- 5. Disk space (storage_path). --}}
        <x-ui.card wire:key="health-disk">
            <div class="flex items-start justify-between gap-3">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('health.disk.label') }}</p>
                <span class="mt-1 inline-flex h-2.5 w-2.5 shrink-0 rounded-full {{ $this->stateDotClass($this->diskStatus['state']) }}" aria-hidden="true"></span>
            </div>

            @if ($this->diskStatus['usedPercent'] === null)
                <p class="mt-3 text-sm text-gray-400 dark:text-gray-500">{{ __('health.unavailable') }}</p>
            @else
                <p @class([
                    'mt-3 text-2xl font-semibold tabular-nums',
                    'text-status-rejected' => $this->diskStatus['state'] === 'error',
                    'text-status-review' => $this->diskStatus['state'] === 'warn',
                    'text-gray-900 dark:text-white' => $this->diskStatus['state'] === 'ok',
                ])>{{ __('health.disk.used_percent', ['percent' => $this->diskStatus['usedPercent']]) }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    {{ __('health.disk.used_of_total', [
                        'used' => $this->formatGb($this->diskStatus['totalBytes'] - $this->diskStatus['freeBytes']),
                        'total' => $this->formatGb($this->diskStatus['totalBytes']),
                    ]) }}
                </p>
            @endif
        </x-ui.card>

        {{-- 6. Scheduler expectations (static, informational). --}}
        <x-ui.card wire:key="health-schedule">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('health.schedule.label') }}</p>
            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">{{ __('health.schedule.description') }}</p>

            <ul class="mt-3 space-y-2">
                @foreach ($this->scheduleExpectations as $row)
                    <li wire:key="health-schedule-{{ $loop->index }}" class="flex items-center justify-between gap-3 text-sm">
                        <span class="text-gray-700 dark:text-gray-200">{{ $row['label'] }}</span>
                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $row['schedule'] }}</span>
                    </li>
                @endforeach
            </ul>
        </x-ui.card>
    </div>
</div>

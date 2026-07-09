<?php

namespace App\Livewire\Admin;

use App\Models\Aid;
use App\Models\MessageLog;
use App\Models\RecurringAidPlan;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Throwable;

/**
 * Admin-only "system health" overview: a grid of small status cards that
 * surface operational signals which don't otherwise appear anywhere in the
 * UI — the queue backlog, recent message failures, whether the recurring
 * aid scheduler is actually running, backup freshness, and disk space.
 *
 * Every computed method here is defensive: an unavailable table, an
 * unreadable disk, or a filesystem error never bubbles up as a 500 — it is
 * caught and rendered as an "unknown"/"غير متاح" card state instead. Only
 * portable SQL/PHP is used (no MySQL-specific functions), since dev runs on
 * SQLite and production on MySQL.
 */
class SystemHealth extends Component
{
    /**
     * Card state, driving the semantic status dot color:
     * ok = green, warn = amber, error = red, unknown = gray (data
     * unavailable rather than actually unhealthy).
     */
    private const STATE_OK = 'ok';

    private const STATE_WARN = 'warn';

    private const STATE_ERROR = 'error';

    private const STATE_UNKNOWN = 'unknown';

    public function mount(): void
    {
        Gate::authorize('settings.view');
    }

    /**
     * @return array{state: string, pending: ?int, failed: ?int}
     */
    #[Computed]
    public function queueStatus(): array
    {
        try {
            $pending = DB::table('jobs')->count();
            $failed = DB::table('failed_jobs')->count();

            return [
                'state' => $failed > 0 ? self::STATE_ERROR : self::STATE_OK,
                'pending' => $pending,
                'failed' => $failed,
            ];
        } catch (Throwable) {
            return [
                'state' => self::STATE_UNKNOWN,
                'pending' => null,
                'failed' => null,
            ];
        }
    }

    /**
     * @return array{state: string, count: ?int}
     */
    #[Computed]
    public function messageFailureStatus(): array
    {
        try {
            $count = MessageLog::query()
                ->where('status', 'failed')
                ->where('created_at', '>=', CarbonImmutable::now()->subDay())
                ->count();

            return [
                'state' => $count > 0 ? self::STATE_ERROR : self::STATE_OK,
                'count' => $count,
            ];
        } catch (Throwable) {
            return [
                'state' => self::STATE_UNKNOWN,
                'count' => null,
            ];
        }
    }

    /**
     * @return array{state: string, lastGeneratedAt: ?string, overdueCount: ?int}
     */
    #[Computed]
    public function recurringStatus(): array
    {
        try {
            $lastGeneratedAt = Aid::query()
                ->whereNotNull('recurring_aid_plan_id')
                ->max('created_at');

            $overdueCount = RecurringAidPlan::query()
                ->where('is_active', true)
                ->where('next_run_on', '<', CarbonImmutable::today()->toDateString())
                ->count();

            $state = self::STATE_OK;

            if ($overdueCount > 0) {
                $state = self::STATE_ERROR;
            } elseif ($lastGeneratedAt === null) {
                $state = self::STATE_WARN;
            }

            return [
                'state' => $state,
                'lastGeneratedAt' => $lastGeneratedAt,
                'overdueCount' => $overdueCount,
            ];
        } catch (Throwable) {
            return [
                'state' => self::STATE_UNKNOWN,
                'lastGeneratedAt' => null,
                'overdueCount' => null,
            ];
        }
    }

    /**
     * @return array{state: string, newestAt: ?string, sizeBytes: ?int, empty: bool}
     */
    #[Computed]
    public function backupStatus(): array
    {
        try {
            $disk = Storage::disk('backups');
            $files = $disk->allFiles();

            if ($files === []) {
                return [
                    'state' => self::STATE_WARN,
                    'newestAt' => null,
                    'sizeBytes' => null,
                    'empty' => true,
                ];
            }

            $newestPath = null;
            $newestTimestamp = null;

            foreach ($files as $path) {
                try {
                    $timestamp = $disk->lastModified($path);
                } catch (Throwable) {
                    continue;
                }

                if ($timestamp === false) {
                    continue;
                }

                if ($newestTimestamp === null || $timestamp > $newestTimestamp) {
                    $newestTimestamp = $timestamp;
                    $newestPath = $path;
                }
            }

            if ($newestPath === null || $newestTimestamp === null) {
                return [
                    'state' => self::STATE_WARN,
                    'newestAt' => null,
                    'sizeBytes' => null,
                    'empty' => true,
                ];
            }

            $sizeBytes = null;

            try {
                $size = $disk->size($newestPath);
                $sizeBytes = $size !== false ? $size : null;
            } catch (Throwable) {
                $sizeBytes = null;
            }

            $newestAt = CarbonImmutable::createFromTimestamp($newestTimestamp);
            $isStale = $newestAt->lt(CarbonImmutable::now()->subHours(48));

            return [
                'state' => $isStale ? self::STATE_ERROR : self::STATE_OK,
                'newestAt' => $newestAt->toDateTimeString(),
                'sizeBytes' => $sizeBytes,
                'empty' => false,
            ];
        } catch (Throwable) {
            return [
                'state' => self::STATE_UNKNOWN,
                'newestAt' => null,
                'sizeBytes' => null,
                'empty' => false,
            ];
        }
    }

    /**
     * @return array{state: string, freeBytes: ?float, totalBytes: ?float, usedPercent: ?float}
     */
    #[Computed]
    public function diskStatus(): array
    {
        try {
            $path = storage_path();
            $free = @disk_free_space($path);
            $total = @disk_total_space($path);

            if ($free === false || $total === false || $total === null || $free === null || (float) $total <= 0.0) {
                return [
                    'state' => self::STATE_UNKNOWN,
                    'freeBytes' => null,
                    'totalBytes' => null,
                    'usedPercent' => null,
                ];
            }

            $usedPercent = (($total - $free) / $total) * 100;

            $state = self::STATE_OK;

            if ($usedPercent > 90) {
                $state = self::STATE_ERROR;
            } elseif ($usedPercent > 80) {
                $state = self::STATE_WARN;
            }

            return [
                'state' => $state,
                'freeBytes' => (float) $free,
                'totalBytes' => (float) $total,
                'usedPercent' => round($usedPercent, 1),
            ];
        } catch (Throwable) {
            return [
                'state' => self::STATE_UNKNOWN,
                'freeBytes' => null,
                'totalBytes' => null,
                'usedPercent' => null,
            ];
        }
    }

    /**
     * Static, informational rows describing when the scheduled commands are
     * *expected* to run (there is no live heartbeat signal to read — the
     * recurring-generation and backup cards above are the closest thing to
     * one). Purely descriptive, so it never fails.
     *
     * @return list<array{label: string, schedule: string}>
     */
    #[Computed]
    public function scheduleExpectations(): array
    {
        return [
            ['label' => __('health.schedule.backup_row'), 'schedule' => __('health.schedule.backup_time')],
            ['label' => __('health.schedule.recurring_row'), 'schedule' => __('health.schedule.daily')],
            ['label' => __('health.schedule.reminders_row'), 'schedule' => __('health.schedule.daily')],
        ];
    }

    /**
     * Formats a byte count as a GB figure with two decimal places — used
     * for the disk-space card, which always reports in GB per spec.
     */
    public function formatGb(?float $bytes): ?string
    {
        if ($bytes === null) {
            return null;
        }

        return number_format($bytes / 1_073_741_824, 2);
    }

    /**
     * Formats a byte count adaptively (KB/MB/GB) — used for the backup
     * card, where archive sizes vary widely.
     */
    public function formatBytes(?int $bytes): ?string
    {
        if ($bytes === null) {
            return null;
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $value = (float) $bytes;
        $unitIndex = 0;

        while ($value >= 1024 && $unitIndex < count($units) - 1) {
            $value /= 1024;
            $unitIndex++;
        }

        return number_format($value, $unitIndex === 0 ? 0 : 2).' '.$units[$unitIndex];
    }

    /**
     * Tailwind class for the semantic status dot matching a card's state —
     * reusing the same --color-status-* tokens the rest of the app uses for
     * aid-status badges (draft/review/approved/rejected).
     */
    public function stateDotClass(string $state): string
    {
        return match ($state) {
            self::STATE_OK => 'bg-status-approved',
            self::STATE_WARN => 'bg-status-review',
            self::STATE_ERROR => 'bg-status-rejected',
            default => 'bg-status-draft',
        };
    }

    public function render()
    {
        return view('livewire.admin.system-health');
    }
}

<?php

namespace App\Livewire;

use App\Enums\AidStatus;
use App\Enums\AidType;
use App\Enums\BeneficiaryStatus;
use App\Enums\ReceiptStatus;
use App\Models\Aid;
use App\Models\AidConfirmation;
use App\Models\Beneficiary;
use App\Models\RecurringAidPlan;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Dashboard extends Component
{
    /**
     * Maps an AidStatus's semantic color token (see AidStatus::color())
     * to the hex value it resolves to in resources/css/app.css's
     * --color-status-* tokens, per CLAUDE.md's fixed status palette.
     * Charts need real hex values (ApexCharts colors are not CSS-aware),
     * while the rest of the UI reads these through the `status-*` Tailwind
     * tokens directly.
     *
     * @var array<string, string>
     */
    private const STATUS_COLOR_HEX = [
        'draft' => '#6B7280',
        'review' => '#D97706',
        'approved' => '#16A34A',
        'rejected' => '#DC2626',
        'delivered' => '#44631F',
    ];

    /**
     * How many days without movement before an under-review aid / a
     * beneficiary under review is flagged as "overdue" on the operational
     * indicators row.
     */
    private const OVERDUE_DAYS = 7;

    /**
     * How many days ahead a recurring plan's next run is considered
     * "due soon" on the operational indicators row.
     */
    private const UPCOMING_RECURRING_DAYS = 7;

    #[Computed]
    public function beneficiariesCount(): int
    {
        return Beneficiary::query()->count();
    }

    #[Computed]
    public function aidsCount(): int
    {
        return Aid::query()->count();
    }

    /**
     * Aids approved during the current calendar month (Gregorian), by
     * decision date.
     */
    #[Computed]
    public function approvedThisMonth(): int
    {
        return Aid::query()
            ->where('status', AidStatus::Approved->value)
            ->whereBetween('decided_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();
    }

    /**
     * Aids currently sitting under review at a stage whose role the
     * signed-in user holds — mirrors Approvals\Inbox::pendingAtMyStages().
     * Zero (and hidden card) for users without approvals.view.
     */
    #[Computed]
    public function pendingForMyRole(): int
    {
        $user = Auth::user();

        if (! $user->can('approvals.view')) {
            return 0;
        }

        return Aid::query()
            ->where('status', AidStatus::UnderReview->value)
            ->whereHas('currentStage', function (Builder $query) use ($user): void {
                $query->whereIn('role', $user->getRoleNames());
            })
            ->count();
    }

    /**
     * Aids stuck under review for longer than {@see OVERDUE_DAYS} since
     * they were submitted — the first "pending indicators" card. Zero for
     * users without approvals.view (the card is also hidden by @can in the
     * view; this is defense in depth, mirrors pendingForMyRole()).
     */
    #[Computed]
    public function overdueApprovalsCount(): int
    {
        if (! Auth::user()->can('approvals.view')) {
            return 0;
        }

        return Aid::query()
            ->where('status', AidStatus::UnderReview->value)
            ->whereNotNull('submitted_at')
            ->where('submitted_at', '<', now()->subDays(self::OVERDUE_DAYS))
            ->count();
    }

    /**
     * Confirmation links that expired without the beneficiary responding,
     * for aids that are still sitting in Delivered (i.e. never advanced to
     * Confirmed) — signals deliveries that likely need a follow-up call or
     * a resent link.
     */
    #[Computed]
    public function expiredConfirmationsCount(): int
    {
        if (! Auth::user()->can('aids.view')) {
            return 0;
        }

        return AidConfirmation::query()
            ->whereNull('confirmed_at')
            ->where('expires_at', '<', now())
            ->whereHas('aid', function (Builder $query): void {
                $query->where('status', AidStatus::Delivered->value);
            })
            ->count();
    }

    /**
     * Active recurring aid plans whose next cycle is due within
     * {@see UPCOMING_RECURRING_DAYS} — a heads-up before the aids they
     * generate need review/approval.
     */
    #[Computed]
    public function upcomingRecurringPlansCount(): int
    {
        if (! Auth::user()->can('aids.view')) {
            return 0;
        }

        return RecurringAidPlan::query()
            ->where('is_active', true)
            ->whereNotNull('next_run_on')
            ->where('next_run_on', '<=', now()->addDays(self::UPCOMING_RECURRING_DAYS)->toDateString())
            ->count();
    }

    /**
     * Beneficiaries whose case has sat under review for longer than
     * {@see OVERDUE_DAYS} without an update — a case that may have stalled.
     */
    #[Computed]
    public function overdueBeneficiaryReviewsCount(): int
    {
        if (! Auth::user()->can('beneficiaries.view')) {
            return 0;
        }

        return Beneficiary::query()
            ->where('status', BeneficiaryStatus::UnderReview->value)
            ->where('updated_at', '<', now()->subDays(self::OVERDUE_DAYS))
            ->count();
    }

    /**
     * The receipt outcomes that need staff follow-up (anything short of a
     * full receipt), used by both the count and the short list below.
     *
     * @return array<int, string>
     */
    private function attentionReceiptValues(): array
    {
        return array_map(
            fn (ReceiptStatus $status): string => $status->value,
            array_filter(ReceiptStatus::cases(), fn (ReceiptStatus $s): bool => $s->needsAttention()),
        );
    }

    /**
     * Number of aids whose beneficiary reported a partial or missing receipt
     * on the public confirmation page — derived from the AidConfirmation
     * relationship, never a column on aids.
     */
    #[Computed]
    public function receiptIssuesCount(): int
    {
        return Aid::query()
            ->whereHas('confirmation', function (Builder $query): void {
                $query->whereIn('receipt_status', $this->attentionReceiptValues());
            })
            ->count();
    }

    /**
     * The most recent handful of aids flagged as partially / not received,
     * for the dashboard follow-up list.
     *
     * @return Collection<int, Aid>
     */
    #[Computed]
    public function receiptIssues(): Collection
    {
        return Aid::query()
            ->with(['beneficiary:id,first_name,second_name,third_name,last_name', 'program:id,name', 'confirmation:id,aid_id,receipt_status,confirmed_at'])
            ->whereHas('confirmation', function (Builder $query): void {
                $query->whereIn('receipt_status', $this->attentionReceiptValues());
            })
            ->latest()
            ->limit(5)
            ->get();
    }

    /**
     * Aid counts per status, in enum declaration order, with the hex color
     * each status's semantic token resolves to.
     *
     * @return array{labels: array<int, string>, series: array<int, int>, colors: array<int, string>}
     */
    #[Computed]
    public function aidsByStatus(): array
    {
        // Plain GROUP BY status — portable across MySQL/SQLite, no
        // date-truncation functions involved.
        $counts = Aid::query()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $labels = [];
        $series = [];
        $colors = [];

        foreach (AidStatus::cases() as $status) {
            $labels[] = $status->label();
            $series[] = (int) ($counts[$status->value] ?? 0);
            $colors[] = self::STATUS_COLOR_HEX[$status->color()];
        }

        return ['labels' => $labels, 'series' => $series, 'colors' => $colors];
    }

    /**
     * Aid counts per type (cash / in-kind).
     *
     * @return array{labels: array<int, string>, series: array<int, int>, colors: array<int, string>}
     */
    #[Computed]
    public function aidsByType(): array
    {
        $counts = Aid::query()
            ->selectRaw('type, count(*) as aggregate')
            ->groupBy('type')
            ->pluck('aggregate', 'type');

        $labels = [];
        $series = [];

        foreach (AidType::cases() as $type) {
            $labels[] = $type->label();
            $series[] = (int) ($counts[$type->value] ?? 0);
        }

        // Brand primary/secondary tokens (CLAUDE.md), in AidType declaration order.
        return ['labels' => $labels, 'series' => $series, 'colors' => ['#1C545E', '#85BF40']];
    }

    /**
     * Aid count and cash total per month, for the trailing 12 months
     * (including the current one). The month grouping happens in PHP via
     * Collection::groupBy() on Carbon's format('Y-m') — never a
     * MySQL-only DATE_FORMAT()/SQLite-only strftime() — so this works
     * identically on both this app's dev (SQLite) and production (MySQL)
     * databases.
     *
     * @return array{labels: array<int, string>, counts: array<int, int>, cashSums: array<int, float>}
     */
    #[Computed]
    public function aidsByMonth(): array
    {
        $start = now()->subMonths(11)->startOfMonth();
        $end = now()->endOfMonth();

        $aids = Aid::query()
            ->select(['created_at', 'amount', 'type'])
            ->whereBetween('created_at', [$start, $end])
            ->get();

        $grouped = $aids->groupBy(fn (Aid $aid): string => $aid->created_at->format('Y-m'));

        $labels = [];
        $counts = [];
        $cashSums = [];

        for ($i = 0; $i < 12; $i++) {
            /** @var Carbon $month */
            $month = $start->copy()->addMonths($i);
            $key = $month->format('Y-m');
            $bucket = $grouped->get($key, collect());

            $labels[] = $key;
            $counts[] = $bucket->count();
            $cashSums[] = (float) $bucket
                ->where('type', AidType::Cash)
                ->sum(fn (Aid $aid): float => (float) $aid->amount);
        }

        return ['labels' => $labels, 'counts' => $counts, 'cashSums' => $cashSums];
    }

    public function render()
    {
        return view('livewire.dashboard');
    }
}

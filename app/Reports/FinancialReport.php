<?php

namespace App\Reports;

use App\Enums\AidStatus;
use App\Enums\AidType;
use App\Models\Aid;
use App\Reports\Contracts\Report;
use App\Reports\Filters\FinancialFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

/**
 * Monthly/yearly financial report: rows are a (program × month) grouping
 * keyed off `decided_at` — a temporary decision (per the phase-7 scope)
 * until a dedicated disbursement date exists. The grouping itself is done
 * in PHP via Collection::groupBy() on `decided_at->format('Y-m')`, never
 * via MySQL-only SQL (this app must also run on SQLite).
 *
 * Only financially "real" aids are included: Approved, InDisbursement or
 * Delivered — i.e. aids that were actually granted, excluding rejected
 * ones even though they also carry a decided_at timestamp.
 */
final class FinancialReport implements Report
{
    private const INCLUDED_STATUSES = [
        AidStatus::Approved,
        AidStatus::InDisbursement,
        AidStatus::Delivered,
    ];

    public function __construct(private readonly FinancialFilter $filter) {}

    public function authorize(): bool
    {
        return Gate::allows('reports.view');
    }

    /**
     * The underlying (non-grouped) aid query. Kept for interface
     * compliance and simple counts; the displayed/exported rows come from
     * rows(), which groups this same filtered set by program and month.
     *
     * @return Builder<Aid>
     */
    public function query(): Builder
    {
        return Aid::query()
            ->with(['program:id,name', 'items'])
            ->whereNotNull('decided_at')
            ->whereIn('status', array_map(fn (AidStatus $status): string => $status->value, self::INCLUDED_STATUSES))
            ->when($this->filter->from, fn (Builder $q) => $q->whereDate('decided_at', '>=', $this->filter->from))
            ->when($this->filter->to, fn (Builder $q) => $q->whereDate('decided_at', '<=', $this->filter->to))
            ->when($this->filter->programId, fn (Builder $q) => $q->where('aid_program_id', $this->filter->programId))
            ->orderBy('decided_at');
    }

    /**
     * Grouped rows: one per (program, month), sorted chronologically.
     *
     * @return Collection<int, array{program: string, month: string, count: int, cash_total: float, in_kind_total: float, grand_total: float}>
     */
    public function rows(): Collection
    {
        return $this->query()->get()
            ->groupBy(fn (Aid $aid): string => ($aid->program?->name ?? __('reports.financial.no_program')).'|'.$aid->decided_at->format('Y-m'))
            ->map(function (Collection $group): array {
                /** @var Aid $first */
                $first = $group->first();

                $cashTotal = (float) $group->where('type', AidType::Cash)->sum(fn (Aid $aid): float => (float) $aid->amount);
                $inKindTotal = (float) $group->where('type', AidType::InKind)->sum(
                    fn (Aid $aid): float => (float) $aid->items->sum(fn ($item): float => $item->quantity * (float) $item->estimated_value),
                );

                return [
                    'program' => $first->program?->name ?? __('reports.financial.no_program'),
                    'month' => $first->decided_at->format('Y-m'),
                    'count' => $group->count(),
                    'cash_total' => $cashTotal,
                    'in_kind_total' => $inKindTotal,
                    'grand_total' => $cashTotal + $inKindTotal,
                ];
            })
            ->values()
            ->sortBy('month')
            ->values();
    }

    public function headings(): array
    {
        return [
            __('reports.financial.column_program'),
            __('reports.financial.column_month'),
            __('reports.financial.column_count'),
            __('reports.financial.column_cash_total'),
            __('reports.financial.column_in_kind_total'),
            __('reports.financial.column_grand_total'),
        ];
    }

    /**
     * @param  array{program: string, month: string, count: int, cash_total: float, in_kind_total: float, grand_total: float}  $row
     */
    public function map($row): array
    {
        return [
            $row['program'],
            $row['month'],
            $row['count'],
            $row['cash_total'],
            $row['in_kind_total'],
            $row['grand_total'],
        ];
    }

    public function totals(): array
    {
        $rows = $this->rows();

        return [
            'count' => (int) $rows->sum('count'),
            'cash_total' => (float) $rows->sum('cash_total'),
            'in_kind_total' => (float) $rows->sum('in_kind_total'),
            'grand_total' => (float) $rows->sum('grand_total'),
        ];
    }

    public function title(): string
    {
        return __('reports.financial.title');
    }

    public function filename(string $ext): string
    {
        return 'financial-report-'.now()->format('Y-m-d').'.'.$ext;
    }

    public function pdfView(): string
    {
        return 'reports.pdf.financial';
    }
}

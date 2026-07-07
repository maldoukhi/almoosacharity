<?php

namespace App\Reports;

use App\Models\Aid;
use App\Models\Beneficiary;
use App\Reports\Contracts\Report;
use App\Reports\Filters\BeneficiariesFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

/**
 * Beneficiaries report: one row per beneficiary with their categories, aid
 * count, and last aid date. The aid count/last-aid-date are computed via
 * correlated subqueries against the Aid model (rather than a new relation
 * on Beneficiary, to avoid touching a model shared with other in-flight
 * work) and therefore already respect Aid's own soft-delete scope.
 * Soft-deleted beneficiaries are excluded by Eloquent's default scope.
 */
final class BeneficiariesReport implements Report
{
    public function __construct(private readonly BeneficiariesFilter $filter) {}

    public function authorize(): bool
    {
        return Gate::allows('reports.view');
    }

    /**
     * @return Builder<Beneficiary>
     */
    public function query(): Builder
    {
        return Beneficiary::query()
            ->with('categories')
            ->select('beneficiaries.*')
            ->selectSub(
                Aid::query()->selectRaw('count(*)')->whereColumn('beneficiary_id', 'beneficiaries.id'),
                'aids_count',
            )
            ->selectSub(
                Aid::query()->select('created_at')->whereColumn('beneficiary_id', 'beneficiaries.id')->latest('created_at')->limit(1),
                'last_aid_at',
            )
            ->when($this->filter->from, fn (Builder $q) => $q->whereDate('beneficiaries.created_at', '>=', $this->filter->from))
            ->when($this->filter->to, fn (Builder $q) => $q->whereDate('beneficiaries.created_at', '<=', $this->filter->to))
            ->when($this->filter->status, fn (Builder $q) => $q->where('status', $this->filter->status))
            ->when($this->filter->categoryId, function (Builder $q): void {
                $q->whereHas('categories', fn (Builder $q) => $q->where('beneficiary_categories.id', $this->filter->categoryId));
            })
            ->when($this->filter->city, fn (Builder $q) => $q->where('city', $this->filter->city))
            ->orderBy('last_name')
            ->orderBy('first_name');
    }

    public function headings(): array
    {
        return [
            __('reports.beneficiaries.column_full_name'),
            __('reports.beneficiaries.column_national_id'),
            __('reports.beneficiaries.column_mobile'),
            __('reports.beneficiaries.column_city'),
            __('reports.beneficiaries.column_categories'),
            __('reports.beneficiaries.column_status'),
            __('reports.beneficiaries.column_aids_count'),
            __('reports.beneficiaries.column_last_aid_at'),
        ];
    }

    /**
     * @param  Beneficiary  $row
     */
    public function map($row): array
    {
        return [
            $row->full_name,
            $row->national_id,
            $row->mobile,
            $row->city,
            $row->categories->pluck('name')->implode('، '),
            $row->status->label(),
            (int) $row->aids_count,
            $row->last_aid_at ? Carbon::parse($row->last_aid_at)->format('Y-m-d') : null,
        ];
    }

    public function totals(): array
    {
        return [
            'count' => $this->query()->count(),
        ];
    }

    public function title(): string
    {
        return __('reports.beneficiaries.title');
    }

    public function filename(string $ext): string
    {
        return 'beneficiaries-report-'.now()->format('Y-m-d').'.'.$ext;
    }

    public function pdfView(): string
    {
        return 'reports.pdf.beneficiaries';
    }
}

<?php

namespace App\Reports;

use App\Models\Aid;
use App\Reports\Contracts\Report;
use App\Reports\Filters\SurveysFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

/**
 * Survey results report — STUB.
 *
 * The surveys domain (phase 6, built in parallel) has not landed yet, so
 * there is no SurveyResponse model/table to query. This class keeps the
 * final shape (headings/columns) already decided so the surveys agent can
 * later swap query()/rows() for the real model without touching the
 * Livewire screen, export, or PDF view built against this report.
 *
 * query() intentionally returns a guaranteed-empty result set built off
 * the Aid model purely to satisfy the Report::query(): Builder contract
 * (paginate()/count() on it behave normally and always report zero rows).
 */
final class SurveysReport implements Report
{
    public function __construct(private readonly SurveysFilter $filter) {}

    public function authorize(): bool
    {
        return Gate::allows('reports.view');
    }

    /**
     * @return Builder<Aid>
     */
    public function query(): Builder
    {
        return Aid::query()->whereRaw('1 = 0');
    }

    public function headings(): array
    {
        return [
            __('reports.surveys.column_survey'),
            __('reports.surveys.column_question'),
            __('reports.surveys.column_response'),
            __('reports.surveys.column_submitted_at'),
        ];
    }

    public function map($row): array
    {
        return [];
    }

    public function totals(): array
    {
        return [
            'count' => 0,
        ];
    }

    public function title(): string
    {
        return __('reports.surveys.title');
    }

    public function filename(string $ext): string
    {
        return 'surveys-report-'.now()->format('Y-m-d').'.'.$ext;
    }

    public function pdfView(): string
    {
        return 'reports.pdf.surveys';
    }
}

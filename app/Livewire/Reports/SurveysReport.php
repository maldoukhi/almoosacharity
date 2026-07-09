<?php

namespace App\Livewire\Reports;

use App\Exports\SurveysExport;
use App\Models\AidProgram;
use App\Models\Survey;
use App\Reports\Filters\SurveysFilter;
use App\Reports\SurveysReport as SurveysReportData;
use App\Support\ArabicPdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Survey results report screen: filter bar (survey selector required-or-all,
 * aid program, submission date range), a responses/surveys totals header,
 * per-survey per-question aggregate cards, and Excel/PDF export. Mirrors the
 * structure/conventions of {@see AidsReport}.
 */
class SurveysReport extends Component
{
    #[Url]
    public string $survey = '';

    #[Url]
    public string $program = '';

    #[Url]
    public string $from = '';

    #[Url]
    public string $to = '';

    public function mount(): void
    {
        Gate::authorize('reports.view');
    }

    #[Computed]
    public function report(): SurveysReportData
    {
        return new SurveysReportData(new SurveysFilter(
            surveyId: $this->survey !== '' ? (int) $this->survey : null,
            from: $this->from !== '' ? $this->from : null,
            to: $this->to !== '' ? $this->to : null,
            programId: $this->program !== '' ? (int) $this->program : null,
        ));
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    #[Computed]
    public function surveys(): Collection
    {
        return $this->report->surveys();
    }

    #[Computed]
    public function totals(): array
    {
        return $this->report->totals();
    }

    /**
     * @return Collection<int, Survey>
     */
    #[Computed]
    public function surveyOptions(): Collection
    {
        return Survey::query()->orderBy('title')->get(['id', 'title']);
    }

    /**
     * @return Collection<int, AidProgram>
     */
    #[Computed]
    public function programs(): Collection
    {
        return AidProgram::query()->orderBy('sort_order')->get();
    }

    public function exportExcel(): BinaryFileResponse
    {
        Gate::authorize('reports.export');

        return Excel::download(new SurveysExport($this->report), $this->report->filename('xlsx'));
    }

    public function exportPdf(): StreamedResponse
    {
        Gate::authorize('reports.export');

        $report = $this->report;

        $rows = $report->rows()->map(fn (array $row): array => $report->map($row));

        $pdf = ArabicPdf::loadView($report->pdfView(), [
            'title' => $report->title(),
            'headings' => $report->headings(),
            'rows' => $rows,
            'totals' => $report->totals(),
            'filtersDescription' => $this->filtersDescription(),
        ]);

        return response()->streamDownload(
            fn () => print ($pdf->output()),
            $report->filename('pdf'),
        );
    }

    private function filtersDescription(): string
    {
        $parts = [];

        if ($this->survey !== '') {
            $parts[] = __('reports.filters.survey').': '.optional(Survey::find($this->survey))->title;
        }

        if ($this->program !== '') {
            $parts[] = __('reports.filters.program').': '.optional(AidProgram::find($this->program))->name;
        }

        if ($this->from !== '') {
            $parts[] = __('reports.filters.from').': '.$this->from;
        }

        if ($this->to !== '') {
            $parts[] = __('reports.filters.to').': '.$this->to;
        }

        return implode(' | ', $parts);
    }

    public function render()
    {
        return view('livewire.reports.surveys-report');
    }
}

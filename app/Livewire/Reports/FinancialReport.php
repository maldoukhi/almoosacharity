<?php

namespace App\Livewire\Reports;

use App\Exports\FinancialExport;
use App\Models\AidProgram;
use App\Reports\Filters\FinancialFilter;
use App\Reports\FinancialReport as FinancialReportData;
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
 * Monthly/yearly financial report screen: filter bar, totals cards, and a
 * (program × month) preview table. Rows come from
 * FinancialReport::rows() — already grouped in PHP — rather than a
 * paginated query, since the row count (programs × months in range) is
 * naturally small.
 */
class FinancialReport extends Component
{
    #[Url]
    public string $from = '';

    #[Url]
    public string $to = '';

    #[Url]
    public string $program = '';

    public function mount(): void
    {
        Gate::authorize('reports.view');
    }

    #[Computed]
    public function report(): FinancialReportData
    {
        return new FinancialReportData(new FinancialFilter(
            from: $this->from !== '' ? $this->from : null,
            to: $this->to !== '' ? $this->to : null,
            programId: $this->program !== '' ? (int) $this->program : null,
        ));
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    #[Computed]
    public function rows(): Collection
    {
        return $this->report->rows();
    }

    #[Computed]
    public function totals(): array
    {
        return $this->report->totals();
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

        return Excel::download(new FinancialExport($this->report), $this->report->filename('xlsx'));
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

        if ($this->from !== '') {
            $parts[] = __('reports.filters.from').': '.$this->from;
        }

        if ($this->to !== '') {
            $parts[] = __('reports.filters.to').': '.$this->to;
        }

        if ($this->program !== '') {
            $parts[] = __('reports.filters.program').': '.optional(AidProgram::find($this->program))->name;
        }

        return implode(' | ', $parts);
    }

    public function render()
    {
        return view('livewire.reports.financial-report');
    }
}

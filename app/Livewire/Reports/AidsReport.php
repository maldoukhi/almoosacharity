<?php

namespace App\Livewire\Reports;

use App\Enums\AidStatus;
use App\Enums\AidType;
use App\Exports\AidsExport;
use App\Models\Aid;
use App\Models\AidProgram;
use App\Reports\AidsReport as AidsReportData;
use App\Reports\Filters\AidsFilter;
use App\Support\ArabicPdf;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Detailed aids report screen: filter bar, totals cards, a preview table,
 * and Excel/PDF export.
 */
class AidsReport extends Component
{
    use WithPagination;

    #[Url]
    public string $from = '';

    #[Url]
    public string $to = '';

    #[Url]
    public string $status = '';

    #[Url]
    public string $program = '';

    #[Url]
    public string $type = '';

    /**
     * Stub filter: accepted in the UI but not yet applied — the aids table
     * has no delivery_method column until the disbursement/delivery domain
     * (built in parallel) lands. See App\Reports\Filters\AidsFilter.
     */
    #[Url]
    public string $delivery = '';

    public function mount(): void
    {
        Gate::authorize('reports.view');
    }

    public function updatingFrom(): void
    {
        $this->resetPage();
    }

    public function updatingTo(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function updatingProgram(): void
    {
        $this->resetPage();
    }

    public function updatingType(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function report(): AidsReportData
    {
        return new AidsReportData(new AidsFilter(
            from: $this->from !== '' ? $this->from : null,
            to: $this->to !== '' ? $this->to : null,
            status: $this->status !== '' ? $this->status : null,
            programId: $this->program !== '' ? (int) $this->program : null,
            type: $this->type !== '' ? $this->type : null,
        ));
    }

    /**
     * @return LengthAwarePaginator<int, Aid>
     */
    #[Computed]
    public function rows(): LengthAwarePaginator
    {
        return $this->report->query()->paginate(25);
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

    /**
     * @return array<int, AidStatus>
     */
    #[Computed]
    public function statuses(): array
    {
        return AidStatus::cases();
    }

    /**
     * @return array<int, AidType>
     */
    #[Computed]
    public function types(): array
    {
        return AidType::cases();
    }

    public function exportExcel(): BinaryFileResponse
    {
        Gate::authorize('reports.export');

        return Excel::download(new AidsExport($this->report), $this->report->filename('xlsx'));
    }

    public function exportPdf(): StreamedResponse
    {
        Gate::authorize('reports.export');

        $report = $this->report;

        $rows = $report->query()->get()->map(fn (Model $row): array => $report->map($row));

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

        if ($this->status !== '') {
            $parts[] = __('reports.filters.status').': '.AidStatus::from($this->status)->label();
        }

        if ($this->program !== '') {
            $parts[] = __('reports.filters.program').': '.optional(AidProgram::find($this->program))->name;
        }

        if ($this->type !== '') {
            $parts[] = __('reports.filters.type').': '.AidType::from($this->type)->label();
        }

        return implode(' | ', $parts);
    }

    public function render()
    {
        return view('livewire.reports.aids-report');
    }
}

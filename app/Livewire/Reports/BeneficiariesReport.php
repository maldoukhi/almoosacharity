<?php

namespace App\Livewire\Reports;

use App\Enums\BeneficiaryStatus;
use App\Exports\BeneficiariesExport;
use App\Models\Beneficiary;
use App\Models\BeneficiaryCategory;
use App\Reports\BeneficiariesReport as BeneficiariesReportData;
use App\Reports\Filters\BeneficiariesFilter;
use Barryvdh\DomPDF\Facade\Pdf;
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
 * Beneficiaries report screen: filter bar, totals card, a preview table,
 * and Excel/PDF export.
 */
class BeneficiariesReport extends Component
{
    use WithPagination;

    #[Url]
    public string $from = '';

    #[Url]
    public string $to = '';

    #[Url]
    public string $status = '';

    #[Url]
    public string $category = '';

    #[Url]
    public string $city = '';

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

    public function updatingCategory(): void
    {
        $this->resetPage();
    }

    public function updatingCity(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function report(): BeneficiariesReportData
    {
        return new BeneficiariesReportData(new BeneficiariesFilter(
            from: $this->from !== '' ? $this->from : null,
            to: $this->to !== '' ? $this->to : null,
            status: $this->status !== '' ? $this->status : null,
            categoryId: $this->category !== '' ? (int) $this->category : null,
            city: $this->city !== '' ? $this->city : null,
        ));
    }

    /**
     * @return LengthAwarePaginator<int, Beneficiary>
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
     * @return Collection<int, BeneficiaryCategory>
     */
    #[Computed]
    public function categories(): Collection
    {
        return BeneficiaryCategory::query()->where('is_active', true)->orderBy('sort_order')->get();
    }

    /**
     * @return array<int, BeneficiaryStatus>
     */
    #[Computed]
    public function statuses(): array
    {
        return BeneficiaryStatus::cases();
    }

    /**
     * @return Collection<int, string>
     */
    #[Computed]
    public function cities(): Collection
    {
        return Beneficiary::query()
            ->whereNotNull('city')
            ->where('city', '!=', '')
            ->distinct()
            ->orderBy('city')
            ->pluck('city');
    }

    public function exportExcel(): BinaryFileResponse
    {
        Gate::authorize('reports.export');

        return Excel::download(new BeneficiariesExport($this->report), $this->report->filename('xlsx'));
    }

    public function exportPdf(): StreamedResponse
    {
        Gate::authorize('reports.export');

        $report = $this->report;

        $rows = $report->query()->get()->map(fn (Model $row): array => $report->map($row));

        $pdf = Pdf::loadView($report->pdfView(), [
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
            $parts[] = __('reports.filters.status').': '.BeneficiaryStatus::from($this->status)->label();
        }

        if ($this->category !== '') {
            $parts[] = __('reports.filters.category').': '.optional(BeneficiaryCategory::find($this->category))->name;
        }

        if ($this->city !== '') {
            $parts[] = __('reports.filters.city').': '.$this->city;
        }

        return implode(' | ', $parts);
    }

    public function render()
    {
        return view('livewire.reports.beneficiaries-report');
    }
}

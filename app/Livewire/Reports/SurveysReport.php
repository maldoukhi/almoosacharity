<?php

namespace App\Livewire\Reports;

use App\Reports\Filters\SurveysFilter;
use App\Reports\SurveysReport as SurveysReportData;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Survey results report screen — STUB. Shows a "coming soon" banner;
 * export is disabled in the view (@can('reports.export') still gates the
 * buttons, but there is deliberately no exportExcel()/exportPdf() action
 * wired up yet, since there is no data to export).
 */
class SurveysReport extends Component
{
    public function mount(): void
    {
        Gate::authorize('reports.view');
    }

    #[Computed]
    public function report(): SurveysReportData
    {
        return new SurveysReportData(new SurveysFilter);
    }

    #[Computed]
    public function totals(): array
    {
        return $this->report->totals();
    }

    public function render()
    {
        return view('livewire.reports.surveys-report');
    }
}

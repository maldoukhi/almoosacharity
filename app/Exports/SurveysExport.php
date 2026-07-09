<?php

namespace App\Exports;

use App\Reports\SurveysReport;
use Illuminate\Support\Collection;

class SurveysExport extends ReportExport
{
    public function __construct(private readonly SurveysReport $surveysReport)
    {
        parent::__construct($surveysReport);
    }

    /**
     * SurveysReport's displayable rows are its per-survey aggregates
     * flattened in PHP (see SurveysReport::rows()), not a raw per-response
     * query() result — override the default collection() to export those.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function collection(): Collection
    {
        return $this->surveysReport->rows();
    }
}

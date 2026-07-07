<?php

namespace App\Exports;

use App\Reports\FinancialReport;
use Illuminate\Support\Collection;

class FinancialExport extends ReportExport
{
    public function __construct(private readonly FinancialReport $financialReport)
    {
        parent::__construct($financialReport);
    }

    /**
     * FinancialReport's displayable rows are already grouped in PHP by
     * program/month (see FinancialReport::rows()), not the raw per-aid
     * query() result — override the default collection() to export those.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function collection(): Collection
    {
        return $this->financialReport->rows();
    }
}

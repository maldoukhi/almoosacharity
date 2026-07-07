<?php

namespace App\Exports;

use App\Reports\BeneficiariesReport;

class BeneficiariesExport extends ReportExport
{
    public function __construct(BeneficiariesReport $report)
    {
        parent::__construct($report);
    }
}

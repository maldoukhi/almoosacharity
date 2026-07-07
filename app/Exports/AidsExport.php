<?php

namespace App\Exports;

use App\Reports\AidsReport;

class AidsExport extends ReportExport
{
    public function __construct(AidsReport $report)
    {
        parent::__construct($report);
    }
}

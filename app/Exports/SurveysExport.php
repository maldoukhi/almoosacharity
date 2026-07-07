<?php

namespace App\Exports;

use App\Reports\SurveysReport;

/**
 * STUB export: always produces an empty sheet (besides headings) until the
 * surveys domain (phase 6, built in parallel) lands.
 */
class SurveysExport extends ReportExport
{
    public function __construct(SurveysReport $report)
    {
        parent::__construct($report);
    }
}

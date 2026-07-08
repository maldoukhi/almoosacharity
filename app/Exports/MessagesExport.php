<?php

namespace App\Exports;

use App\Reports\MessagesReport;

class MessagesExport extends ReportExport
{
    public function __construct(MessagesReport $report)
    {
        parent::__construct($report);
    }
}

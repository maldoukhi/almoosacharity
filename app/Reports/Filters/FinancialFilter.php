<?php

namespace App\Reports\Filters;

/**
 * Filters for the monthly/yearly financial report. Dates filter on
 * `decided_at` (the decision timestamp), a temporary decision per phase-7
 * scope until a dedicated disbursement date is available from the
 * disbursement domain (built in parallel).
 */
final readonly class FinancialFilter
{
    public function __construct(
        public ?string $from = null,
        public ?string $to = null,
        public ?int $programId = null,
    ) {}
}

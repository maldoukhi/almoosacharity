<?php

namespace App\Reports\Filters;

/**
 * Filters for the surveys results report. Kept in place ahead of the
 * surveys domain (phase 6, built in parallel) so SurveysReport's shape is
 * already final once results become available.
 */
final readonly class SurveysFilter
{
    public function __construct(
        public ?string $from = null,
        public ?string $to = null,
        public ?int $programId = null,
    ) {}
}

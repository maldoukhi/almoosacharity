<?php

namespace App\Reports\Filters;

/**
 * Filters for the surveys results report: an optional survey selector
 * (null = all surveys), an optional aid-program filter, and a submission
 * date range applied to survey responses.
 */
final readonly class SurveysFilter
{
    public function __construct(
        public ?int $surveyId = null,
        public ?string $from = null,
        public ?string $to = null,
        public ?int $programId = null,
    ) {}
}

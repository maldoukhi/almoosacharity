<?php

namespace App\Reports\Filters;

/**
 * Filters for the detailed aids report. `deliveryMethod` is accepted but not
 * yet applied to the query: the aids table has no delivery_method column
 * until the disbursement/delivery domain (built in parallel) lands, so this
 * stays a documented stub per the phase-7 decision.
 */
final readonly class AidsFilter
{
    public function __construct(
        public ?string $from = null,
        public ?string $to = null,
        public ?string $status = null,
        public ?int $programId = null,
        public ?string $type = null,
        public ?string $deliveryMethod = null,
    ) {}
}

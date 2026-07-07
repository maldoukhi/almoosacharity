<?php

namespace App\Reports\Filters;

/**
 * Filters for the beneficiaries report.
 */
final readonly class BeneficiariesFilter
{
    public function __construct(
        public ?string $from = null,
        public ?string $to = null,
        public ?string $status = null,
        public ?int $categoryId = null,
        public ?string $city = null,
    ) {}
}

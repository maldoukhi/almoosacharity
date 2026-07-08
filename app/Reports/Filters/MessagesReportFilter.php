<?php

namespace App\Reports\Filters;

/**
 * Filters for the sent-messages report. `from`/`to` apply to the
 * effective send date (sent_at, falling back to created_at for rows the
 * gateway hasn't confirmed yet — see MessagesReport::query()).
 */
final readonly class MessagesReportFilter
{
    public function __construct(
        public ?string $from = null,
        public ?string $to = null,
        public ?string $channel = null,
        public ?string $status = null,
        public ?string $source = null,
    ) {}
}

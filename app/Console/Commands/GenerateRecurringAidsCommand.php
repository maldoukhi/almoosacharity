<?php

namespace App\Console\Commands;

use App\Actions\Aids\GenerateRecurringAids;
use Illuminate\Console\Command;

/**
 * Thin console wrapper around {@see GenerateRecurringAids}, scheduled to
 * run daily (see routes/console.php). Kept trivial so the real logic stays
 * unit-testable through the action.
 */
class GenerateRecurringAidsCommand extends Command
{
    protected $signature = 'aids:generate-recurring';

    protected $description = 'Clone every due recurring aid plan into a new draft aid';

    public function handle(GenerateRecurringAids $generateRecurringAids): int
    {
        $count = $generateRecurringAids->handle();

        $this->info("Generated {$count} recurring aid(s).");

        return self::SUCCESS;
    }
}

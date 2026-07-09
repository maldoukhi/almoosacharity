<?php

namespace App\Models;

use App\Actions\Aids\GenerateRecurringAids;
use App\Enums\RecurrenceFrequency;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A schedule attached to an originating aid: each due cycle the
 * {@see GenerateRecurringAids} generator clones the
 * source aid into a fresh draft aid for the same beneficiary/program and
 * advances {@see next_run_on} by the plan's frequency.
 */
#[Fillable([
    'aid_id', 'frequency', 'interval_months',
    'starts_on', 'ends_on', 'next_run_on', 'is_active',
])]
class RecurringAidPlan extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'frequency' => RecurrenceFrequency::class,
            'interval_months' => 'integer',
            'starts_on' => 'date',
            'ends_on' => 'date',
            'next_run_on' => 'date',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Aid, $this>
     */
    public function aid(): BelongsTo
    {
        return $this->belongsTo(Aid::class);
    }
}

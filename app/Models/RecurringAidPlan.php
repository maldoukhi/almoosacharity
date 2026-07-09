<?php

namespace App\Models;

use App\Actions\Aids\GenerateRecurringAids;
use App\Enums\RecurrenceFrequency;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A schedule attached to an originating aid: each due cycle the
 * {@see GenerateRecurringAids} generator clones the
 * source aid into a fresh draft aid for the same beneficiary/program and
 * advances {@see next_run_on} by the plan's frequency.
 *
 * lead_days pulls each cycle's generation forward that many days before its
 * due date (0 = generate exactly on the due date). Every aid the plan
 * generates is linked back through {@see aids()} — the plan's "series".
 */
#[Fillable([
    'aid_id', 'frequency', 'interval_months',
    'starts_on', 'ends_on', 'next_run_on', 'lead_days', 'is_active',
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
            'lead_days' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * The originating (template) aid this plan clones each cycle.
     *
     * @return BelongsTo<Aid, $this>
     */
    public function aid(): BelongsTo
    {
        return $this->belongsTo(Aid::class);
    }

    /**
     * Every aid this plan has generated so far (the "series") — linked by
     * {@see Aid::$recurring_aid_plan_id}. Excludes the originating aid,
     * which is reached through {@see aid()} instead.
     *
     * @return HasMany<Aid, $this>
     */
    public function aids(): HasMany
    {
        return $this->hasMany(Aid::class, 'recurring_aid_plan_id');
    }
}

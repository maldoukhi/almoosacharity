<?php

namespace App\Models;

use App\Actions\Aids\GenerateRecurringAids;
use App\Enums\RecurrenceFrequency;
use Carbon\CarbonInterface;
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
    'aid_id', 'frequency', 'interval_months', 'title_template',
    'starts_on', 'due_on', 'ends_on', 'next_run_on', 'lead_days', 'is_active',
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
            'due_on' => 'date',
            'ends_on' => 'date',
            'next_run_on' => 'date',
            'lead_days' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Render this plan's title_template into a concrete aid title for one
     * cycle. Supported placeholders (all optional): {program}, {beneficiary}
     * (the source beneficiary's short name), {month} + {year} (of the cycle's
     * due date), and {n} (1-based cycle number). Returns null when no
     * template is set, so the generated aid simply carries no custom title.
     */
    public function renderTitle(int $cycle, CarbonInterface $dueDate): ?string
    {
        if ($this->title_template === null || trim($this->title_template) === '') {
            return null;
        }

        $source = $this->relationLoaded('aid') ? $this->aid : $this->aid()->with('program', 'beneficiary')->first();

        $replacements = [
            '{program}' => $source?->program?->name ?? '',
            '{beneficiary}' => $source?->beneficiary?->short_name ?? '',
            '{month}' => $dueDate->translatedFormat('F'),
            '{year}' => $dueDate->format('Y'),
            '{n}' => (string) $cycle,
        ];

        return trim(strtr($this->title_template, $replacements));
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

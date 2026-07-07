<?php

namespace App\Models;

use App\Actions\Confirmations\ConfirmAidReceipt;
use App\Actions\Confirmations\CreateAidConfirmation;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * The beneficiary-facing "confirm receipt" link state for a single aid's
 * delivery: one row per aid (see the unique aid_id constraint), whose
 * token is rotated in place on every resend/reminder rather than kept as
 * history rows. See {@see CreateAidConfirmation}
 * for how it's created/rotated and
 * {@see ConfirmAidReceipt} for how it's
 * consumed.
 */
#[Fillable([
    'aid_id', 'token_hash', 'expires_at', 'sent_at', 'opened_at',
    'confirmed_at', 'reminder_sent_at', 'confirmed_ip',
    'confirmed_user_agent', 'channel',
])]
class AidConfirmation extends Model
{
    use HasFactory, LogsActivity;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'sent_at' => 'datetime',
            'opened_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'reminder_sent_at' => 'datetime',
        ];
    }

    /**
     * Activity log options: track all fillable attributes except the
     * token hash (not useful for an audit trail and best kept out of it
     * on principle, even though it isn't reversible on its own), only
     * log actual changes, and skip empty log entries.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(array_diff($this->getFillable(), ['token_hash']))
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    /**
     * @return BelongsTo<Aid, $this>
     */
    public function aid(): BelongsTo
    {
        return $this->belongsTo(Aid::class);
    }

    /**
     * @return HasMany<SurveyResponse, $this>
     */
    public function surveyResponses(): HasMany
    {
        return $this->hasMany(SurveyResponse::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isConfirmed(): bool
    {
        return $this->confirmed_at !== null;
    }

    /**
     * The stable, one-way digest stored in {@see token_hash}. The raw
     * token itself (embedded in the signed public link) is never
     * persisted anywhere.
     */
    public static function hashToken(string $rawToken): string
    {
        return hash('sha256', $rawToken);
    }
}

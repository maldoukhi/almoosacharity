<?php

namespace App\Models;

use App\Actions\Approvals\RecordBeneficiaryStageResponse;
use App\Actions\Approvals\RequestBeneficiaryStageResponse;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * The beneficiary-facing "respond to this approval stage" link state for a
 * single (aid, stage): one row per pair (see the unique constraint), whose
 * token is rotated in place on every (re)send rather than kept as history
 * rows. Modelled directly on {@see AidConfirmation}.
 *
 * See {@see RequestBeneficiaryStageResponse} for how it is created/rotated
 * and {@see RecordBeneficiaryStageResponse} for how it is consumed.
 */
#[Fillable([
    'aid_id', 'approval_flow_stage_id', 'stage_name', 'token_hash',
    'expires_at', 'sent_at', 'opened_at', 'responded_at', 'note',
    'responded_ip', 'responded_user_agent', 'channel',
])]
class BeneficiaryStageResponse extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, LogsActivity;

    /**
     * The optional document the beneficiary uploads on the public response
     * page. Private (never the public disk), single file, replaced on
     * re-upload.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('beneficiary_stage_document')->useDisk('local')->singleFile();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'sent_at' => 'datetime',
            'opened_at' => 'datetime',
            'responded_at' => 'datetime',
        ];
    }

    /**
     * Track every attribute except the token hash (kept out of the audit
     * trail on principle), only actual changes, no empty entries.
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
     * @return BelongsTo<ApprovalFlowStage, $this>
     */
    public function stage(): BelongsTo
    {
        return $this->belongsTo(ApprovalFlowStage::class, 'approval_flow_stage_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isResponded(): bool
    {
        return $this->responded_at !== null;
    }

    /**
     * The stable, one-way digest stored in {@see token_hash}. The raw token
     * itself (embedded in the public link) is never persisted anywhere.
     */
    public static function hashToken(string $rawToken): string
    {
        return hash('sha256', $rawToken);
    }
}

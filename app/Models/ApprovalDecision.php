<?php

namespace App\Models;

use App\Enums\ApprovalAction;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * An immutable audit record of a single decision taken on an aid at a
 * given approval stage. Decisions are only ever created, never updated.
 */
#[Fillable(['aid_id', 'approval_flow_stage_id', 'stage_name', 'user_id', 'action', 'note', 'decided_at'])]
class ApprovalDecision extends Model
{
    use LogsActivity;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'action' => ApprovalAction::class,
            'decided_at' => 'datetime',
        ];
    }

    /**
     * Activity log options: track all fillable attributes, only log actual
     * changes, and skip empty log entries.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly($this->getFillable())
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

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

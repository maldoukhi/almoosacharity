<?php

namespace App\Models;

use App\Enums\ApprovalAction;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable(['approval_flow_id', 'name', 'order', 'role', 'allowed_actions'])]
class ApprovalFlowStage extends Model
{
    use LogsActivity;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'order' => 'integer',
            'allowed_actions' => 'array',
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
     * @return BelongsTo<ApprovalFlow, $this>
     */
    public function approvalFlow(): BelongsTo
    {
        return $this->belongsTo(ApprovalFlow::class);
    }

    /**
     * Whether this stage permits the given approval action.
     */
    public function allows(ApprovalAction $action): bool
    {
        return in_array($action->value, $this->allowed_actions ?? [], true);
    }
}

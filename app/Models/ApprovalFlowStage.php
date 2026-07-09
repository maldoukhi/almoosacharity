<?php

namespace App\Models;

use App\Enums\ApprovalAction;
use App\Enums\ApprovalStageType;
use App\Enums\UserStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'approval_flow_id', 'name', 'order', 'role', 'assignee_user_ids',
    'allowed_actions', 'type', 'documents_required', 'required_documents',
    'notify_channels',
])]
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
            'assignee_user_ids' => 'array',
            'type' => ApprovalStageType::class,
            'documents_required' => 'boolean',
            'required_documents' => 'array',
            'notify_channels' => 'array',
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

    /**
     * The user ids specifically assigned to this stage (in addition to,
     * or instead of, the role).
     *
     * @return array<int, int>
     */
    public function assigneeUserIds(): array
    {
        return array_map('intval', $this->assignee_user_ids ?? []);
    }

    /**
     * Whether the given user may act on this stage: either they hold the
     * stage's role, or they are one of its specifically-assigned users.
     * (The `approvals.act` permission is still enforced separately by the
     * policy.)
     */
    public function allowsUser(User $user): bool
    {
        if (filled($this->role) && $user->hasRole($this->role)) {
            return true;
        }

        return in_array((int) $user->id, $this->assigneeUserIds(), true);
    }

    /**
     * Every active user who should be notified/allowed at this stage: the
     * union of the role's holders and the specifically-assigned users.
     *
     * @return Collection<int, User>
     */
    public function eligibleUsers(): Collection
    {
        $ids = $this->assigneeUserIds();

        if (blank($this->role) && $ids === []) {
            return new Collection;
        }

        return User::query()
            ->where('status', UserStatus::Active)
            ->where(function ($query) use ($ids): void {
                if (filled($this->role)) {
                    $query->orWhereHas('roles', fn ($r) => $r->where('name', $this->role));
                }

                if ($ids !== []) {
                    $query->orWhereIn('id', $ids);
                }
            })
            ->get();
    }
}

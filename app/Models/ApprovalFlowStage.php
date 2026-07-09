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
     * Whether this stage waits for the beneficiary's own response (issued
     * as a public link) rather than a staff approve/reject decision. Stages
     * without an explicit type default to a classic approval.
     */
    public function isBeneficiaryResponse(): bool
    {
        return ($this->type ?? ApprovalStageType::Approval) === ApprovalStageType::BeneficiaryResponse;
    }

    /**
     * Whether this stage is a classic staff approve/reject decision (the
     * default when no explicit type is set).
     */
    public function isApproval(): bool
    {
        return ($this->type ?? ApprovalStageType::Approval) === ApprovalStageType::Approval;
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
     * The stage's required document types, normalized to a list of
     * ['label' => string, 'required' => bool] entries with blank labels
     * dropped. Tolerates legacy rows persisted as plain label strings —
     * those are treated as mandatory (required = true).
     *
     * @return array<int, array{label: string, required: bool}>
     */
    public function requiredDocumentTypes(): array
    {
        $types = [];

        foreach ((array) ($this->required_documents ?? []) as $entry) {
            if (is_array($entry)) {
                $label = trim((string) ($entry['label'] ?? ''));
                $required = (bool) ($entry['required'] ?? true);
            } else {
                $label = trim((string) $entry);
                $required = true;
            }

            if ($label === '') {
                continue;
            }

            $types[] = ['label' => $label, 'required' => $required];
        }

        return $types;
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

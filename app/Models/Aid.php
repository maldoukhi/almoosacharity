<?php

namespace App\Models;

use App\Enums\AidStatus;
use App\Enums\AidType;
use App\Models\Concerns\HasHashid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[Fillable([
    'reference', 'beneficiary_id', 'aid_program_id', 'type', 'status',
    'amount', 'purpose', 'notes', 'approval_flow_id', 'current_stage_id',
    'created_by', 'recurring_aid_plan_id', 'submitted_at', 'decided_at',
])]
class Aid extends Model implements HasMedia
{
    use HasFactory, HasHashid, InteractsWithMedia, LogsActivity, SoftDeletes;

    /**
     * Supporting documents attached when the aid is created/edited
     * (quotes, invoices, case files…). These may reference sensitive case
     * details, so — like Disbursement's proof and Beneficiary's documents —
     * they live on the private 'local' disk, never the public one.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('aid_documents')->useDisk('local');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => AidType::class,
            'status' => AidStatus::class,
            'amount' => 'decimal:2',
            'submitted_at' => 'datetime',
            'decided_at' => 'datetime',
        ];
    }

    /**
     * Activity log options: track all fillable attributes, only log actual
     * changes, and skip empty log entries. This gives a full audit trail
     * of every status/stage transition, since those are plain attribute
     * changes on this model.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly($this->getFillable())
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    /**
     * @return BelongsTo<Beneficiary, $this>
     */
    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(Beneficiary::class);
    }

    /**
     * @return BelongsTo<AidProgram, $this>
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(AidProgram::class, 'aid_program_id');
    }

    /**
     * Snapshot of the flow assigned at submission time.
     *
     * @return BelongsTo<ApprovalFlow, $this>
     */
    public function approvalFlow(): BelongsTo
    {
        return $this->belongsTo(ApprovalFlow::class);
    }

    /**
     * @return BelongsTo<ApprovalFlowStage, $this>
     */
    public function currentStage(): BelongsTo
    {
        return $this->belongsTo(ApprovalFlowStage::class, 'current_stage_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<AidItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(AidItem::class);
    }

    /**
     * @return HasMany<ApprovalDecision, $this>
     */
    public function decisions(): HasMany
    {
        return $this->hasMany(ApprovalDecision::class)->latest('decided_at');
    }

    /**
     * @return HasOne<Disbursement, $this>
     */
    public function disbursement(): HasOne
    {
        return $this->hasOne(Disbursement::class);
    }

    /**
     * The single beneficiary delivery-confirmation-link record for this
     * aid (phase 6b) — see {@see AidConfirmation}.
     *
     * @return HasOne<AidConfirmation, $this>
     */
    public function confirmation(): HasOne
    {
        return $this->hasOne(AidConfirmation::class);
    }

    /**
     * The optional recurrence schedule this aid *originates* (phase 10):
     * one plan per template aid that clones it each cycle. Distinct from
     * {@see recurringPlanSeries()}, which points the other way.
     *
     * @return HasOne<RecurringAidPlan, $this>
     */
    public function recurringPlan(): HasOne
    {
        return $this->hasOne(RecurringAidPlan::class);
    }

    /**
     * The recurring plan that *generated* this aid (phase 10 series link),
     * set only on clones the generator produces. Null on aids raised
     * directly, including the plan's own originating aid.
     *
     * @return BelongsTo<RecurringAidPlan, $this>
     */
    public function recurringPlanSeries(): BelongsTo
    {
        return $this->belongsTo(RecurringAidPlan::class, 'recurring_aid_plan_id');
    }
}

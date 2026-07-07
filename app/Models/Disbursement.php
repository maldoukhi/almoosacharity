<?php

namespace App\Models;

use App\Enums\DisbursementMethod;
use App\Enums\DisbursementStatus;
use Database\Factories\DisbursementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[Fillable([
    'aid_id', 'method', 'status', 'started_by', 'started_at',
    'delivered_by', 'delivered_at', 'transfer_reference', 'receipt_number',
    'courier_name', 'bank_account_masked', 'bank_account_holder_snapshot',
    'notes', 'confirmed_by', 'confirmed_at',
])]
class Disbursement extends Model implements HasMedia
{
    /** @use HasFactory<DisbursementFactory> */
    use HasFactory, InteractsWithMedia, LogsActivity;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'method' => DisbursementMethod::class,
            'status' => DisbursementStatus::class,
            'started_at' => 'datetime',
            'delivered_at' => 'datetime',
            'confirmed_at' => 'datetime',
            // The full bank-account-holder snapshot captured when a bank
            // transfer disbursement is started: kept only for internal
            // audit purposes, transparently encrypted at rest, and never
            // exposed to the UI. bank_account_masked is the only
            // representation ever displayed outside this model.
            'bank_account_holder_snapshot' => 'encrypted',
        ];
    }

    /**
     * Activity log options: track all fillable attributes except the
     * encrypted bank snapshot, only log actual changes, and skip empty log
     * entries. This mirrors Beneficiary's/Aid's LogsActivity setup and is
     * mandatory: the bank holder snapshot must never end up in the
     * activity_log.attribute_changes column.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(array_diff($this->getFillable(), ['bank_account_holder_snapshot']))
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    /**
     * A single proof-of-delivery file (photo/PDF) attached when the
     * delivery is recorded.
     */
    public function registerMediaCollections(): void
    {
        // Proof of delivery may reference bank/identity details in some
        // cases, so it must never live on the public disk: same posture as
        // Beneficiary's supporting-document collections.
        $this->addMediaCollection('delivery_proof')->useDisk('local')->singleFile();
    }

    /**
     * @return BelongsTo<Aid, $this>
     */
    public function aid(): BelongsTo
    {
        return $this->belongsTo(Aid::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function startedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'started_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function deliveredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delivered_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }
}

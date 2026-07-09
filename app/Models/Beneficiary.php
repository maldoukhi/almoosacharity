<?php

namespace App\Models;

use App\Enums\BeneficiaryStatus;
use App\Enums\DocumentType;
use App\Enums\Gender;
use App\Enums\HousingType;
use App\Enums\IdType;
use App\Enums\MaritalStatus;
use App\Models\Concerns\HasHashid;
use Database\Factories\BeneficiaryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[Fillable([
    'first_name', 'second_name', 'third_name', 'last_name',
    'id_type', 'national_id', 'nationality', 'birth_date', 'gender',
    'mobile', 'marital_status', 'family_members_count', 'occupation',
    'employer', 'monthly_income', 'health_status', 'special_needs',
    'housing_type', 'rent_amount', 'national_address', 'city', 'district',
    'bank_name', 'iban', 'bank_account_holder', 'status',
    'beneficiary_flow_id', 'current_stage_id', 'submitted_at', 'decided_at',
    'notes', 'created_by',
])]
class Beneficiary extends Model implements HasMedia
{
    /** @use HasFactory<BeneficiaryFactory> */
    use HasFactory, HasHashid, InteractsWithMedia, LogsActivity, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'gender' => Gender::class,
            'marital_status' => MaritalStatus::class,
            'id_type' => IdType::class,
            'housing_type' => HousingType::class,
            'status' => BeneficiaryStatus::class,
            'submitted_at' => 'datetime',
            'decided_at' => 'datetime',
            'family_members_count' => 'integer',
            'monthly_income' => 'decimal:2',
            'rent_amount' => 'decimal:2',
            // Sensitive bank fields: transparently encrypted at rest and
            // decrypted back to plain values by Eloquent. Never expose the
            // full value outside of the bank-data.view/manage permissions;
            // use maskedIban() for anything else.
            'iban' => 'encrypted',
            'bank_account_holder' => 'encrypted',
        ];
    }

    /**
     * Activity log options: track all fillable attributes except the
     * encrypted bank fields (iban, bank_account_holder), only log actual
     * changes, and skip empty log entries. This is mandatory: bank values
     * must never end up in the activity_log.attribute_changes column.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(array_diff($this->getFillable(), ['iban', 'bank_account_holder']))
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    /**
     * One media collection per supported supporting-document type.
     */
    public function registerMediaCollections(): void
    {
        foreach (DocumentType::cases() as $documentType) {
            // Identity/bank documents must never live on the public disk:
            // they are served only through the gated download route.
            $this->addMediaCollection($documentType->value)->useDisk('local');
        }
    }

    /**
     * @return HasMany<BeneficiaryFamilyMember, $this>
     */
    public function familyMembers(): HasMany
    {
        return $this->hasMany(BeneficiaryFamilyMember::class);
    }

    /**
     * @return HasMany<BeneficiaryIncomeSource, $this>
     */
    public function incomeSources(): HasMany
    {
        return $this->hasMany(BeneficiaryIncomeSource::class);
    }

    /**
     * @return BelongsToMany<BeneficiaryCategory, $this>
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(
            BeneficiaryCategory::class,
            'beneficiary_beneficiary_category',
        );
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * The review workflow snapshotted onto this beneficiary at submission
     * time (so later edits to the flow never rewrite an in-flight one).
     *
     * @return BelongsTo<BeneficiaryFlow, $this>
     */
    public function beneficiaryFlow(): BelongsTo
    {
        return $this->belongsTo(BeneficiaryFlow::class);
    }

    /**
     * @return BelongsTo<BeneficiaryFlowStage, $this>
     */
    public function currentStage(): BelongsTo
    {
        return $this->belongsTo(BeneficiaryFlowStage::class, 'current_stage_id');
    }

    /**
     * The decisions recorded against this beneficiary, newest first.
     *
     * @return HasMany<BeneficiaryDecision, $this>
     */
    public function decisions(): HasMany
    {
        return $this->hasMany(BeneficiaryDecision::class)->latest('decided_at');
    }

    /**
     * Full name assembled from the four Arabic name parts, skipping any
     * that are empty.
     */
    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn (): string => collect([
                $this->first_name,
                $this->second_name,
                $this->third_name,
                $this->last_name,
            ])->filter()->implode(' '),
        );
    }

    /**
     * A short, friendly name — first + last only — for greetings in SMS /
     * WhatsApp messages where the full four-part name is too long. Falls
     * back to whichever single part is present.
     */
    protected function shortName(): Attribute
    {
        return Attribute::make(
            get: fn (): string => collect([$this->first_name, $this->last_name])
                ->filter()
                ->implode(' '),
        );
    }

    /**
     * A masked representation of the IBAN safe to display outside of the
     * beneficiaries.bank-data.view permission: reveals only the last 4
     * digits, never the decrypted value in full.
     */
    public function maskedIban(): ?string
    {
        if (! $this->iban) {
            return null;
        }

        return 'SA•• •••• •••• ••'.substr($this->iban, -4);
    }
}

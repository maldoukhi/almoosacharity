<?php

namespace App\Livewire\Beneficiaries\Profile\Concerns;

use App\Enums\BeneficiaryStatus;
use App\Enums\Gender;
use App\Enums\HousingType;
use App\Enums\IdType;
use App\Enums\MaritalStatus;
use App\Models\User;
use App\Support\Countries;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Activity;
use Throwable;

/**
 * Shared formatting helpers for rendering Beneficiary activity-log
 * entries in both the compact timeline (ActivityLog) and the full-detail
 * modal (ActivityDetailModal): translated event titles, enum/date value
 * formatting, and a "changed fields only" diff builder.
 */
trait FormatsBeneficiaryChanges
{
    /**
     * A short, translated title for the activity row/detail header. The
     * bank-data-reveal log entry (a plain ->log() call, not a model
     * event) is special-cased since it never carries an `event` value.
     */
    protected function activityTitle(Activity $activity): string
    {
        if ($activity->log_name === 'bank-data-reveal') {
            return __('beneficiaries.activity.event.bank_data_revealed');
        }

        // Workflow lifecycle activities (SubmitBeneficiary,
        // RecordBeneficiaryDecision, DeactivateBeneficiary, ...) store their
        // description as 'beneficiary.<event>' (e.g. 'beneficiary.reactivated',
        // 'beneficiary.stage-advanced'). Translate those to a readable label,
        // normalizing the hyphenated 'stage-advanced' key to 'stage_advanced',
        // and falling back to the raw description if no translation exists.
        $description = (string) $activity->description;

        if (str_starts_with($description, 'beneficiary.')) {
            $event = str_replace('-', '_', substr($description, strlen('beneficiary.')));
            $key = 'beneficiaries.activity.events.'.$event;

            return __($key) === $key ? $description : __($key);
        }

        return match ($activity->event) {
            'created' => __('beneficiaries.activity.event.created'),
            'updated' => __('beneficiaries.activity.event.updated'),
            'deleted' => __('beneficiaries.activity.event.deleted'),
            'restored' => __('beneficiaries.activity.event.restored'),
            default => $description,
        };
    }

    /**
     * Semantic icon color token for the given activity, matching the
     * fixed aid-workflow status palette (approved=green, review=amber,
     * rejected=red).
     */
    protected function activityIconColor(Activity $activity): string
    {
        return match (true) {
            $activity->log_name === 'bank-data-reveal' => 'bg-status-review/15 text-status-review',
            $activity->event === 'deleted' => 'bg-status-rejected/15 text-status-rejected',
            in_array($activity->event, ['created', 'restored'], true) => 'bg-status-approved/15 text-status-approved',
            default => 'bg-primary-100 text-primary-700 dark:bg-primary-900/40 dark:text-primary-200',
        };
    }

    protected function activityCauserName(Activity $activity): string
    {
        return $activity->causer?->name ?? __('beneficiaries.activity.system_actor');
    }

    /**
     * Only the fields that actually changed value, translated and
     * formatted for display. For a `created` activity every non-empty
     * initial value is listed with `old` left null (nothing to compare
     * against yet). Events that carry no meaningful diff (bank-data-
     * reveal, deleted) return an empty array so the caller can fall back
     * to a simple summary instead of an empty/noisy list.
     *
     * @return array<int, array{field: string, old: ?string, new: ?string}>
     */
    protected function activityChanges(Activity $activity): array
    {
        if ($activity->log_name === 'bank-data-reveal' || $activity->event === 'deleted') {
            return [];
        }

        $old = $activity->attribute_changes['old'] ?? [];
        $new = $activity->attribute_changes['attributes'] ?? [];

        $isCreate = $activity->event === 'created';
        $changes = [];

        foreach (array_unique([...array_keys($old), ...array_keys($new)]) as $field) {
            $formattedOld = $isCreate ? null : $this->formatFieldValue($field, $old[$field] ?? null);
            $formattedNew = $this->formatFieldValue($field, $new[$field] ?? null);

            // Skip fields that never actually changed (blank -> blank, or
            // an identical formatted value on both sides), and skip
            // still-blank fields on creation (nothing meaningful to show).
            if ($isCreate ? $formattedNew === null : $formattedOld === $formattedNew) {
                continue;
            }

            $changes[] = [
                'field' => __('beneficiaries.field_'.$field),
                'old' => $formattedOld,
                'new' => $formattedNew,
            ];
        }

        return $changes;
    }

    private function formatFieldValue(string $field, mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return match ($field) {
            'gender' => Gender::tryFrom((string) $value)?->label() ?? (string) $value,
            'marital_status' => MaritalStatus::tryFrom((string) $value)?->label() ?? (string) $value,
            'id_type' => IdType::tryFrom((string) $value)?->label() ?? (string) $value,
            'housing_type' => HousingType::tryFrom((string) $value)?->label() ?? (string) $value,
            'status' => BeneficiaryStatus::tryFrom((string) $value)?->label() ?? (string) $value,
            'nationality' => Countries::nationalityName((string) $value),
            'birth_date' => $this->formatDateValue((string) $value),
            'created_by' => $this->formatCreatorName((int) $value),
            default => (string) $value,
        };
    }

    private function formatDateValue(string $value): string
    {
        try {
            return Carbon::parse($value)->translatedFormat('Y/m/d');
        } catch (Throwable) {
            return $value;
        }
    }

    private function formatCreatorName(int $id): string
    {
        return User::query()->find($id)?->name ?? ('#'.$id);
    }
}

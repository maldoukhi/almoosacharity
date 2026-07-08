<?php

namespace App\Livewire\Beneficiaries\Profile;

use App\Livewire\Beneficiaries\Profile\Concerns\FormatsBeneficiaryChanges;
use App\Models\Beneficiary;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use LivewireUI\Modal\ModalComponent;
use Spatie\Activitylog\Models\Activity;

/**
 * Read-only detail view for a single beneficiary activity-log entry,
 * opened from the compact timeline row's "details" button. Confirms the
 * requested activity actually belongs to the beneficiary being viewed
 * before rendering anything — a defence against a tampered `activityId`
 * argument, since the id itself carries no authorization on its own.
 */
class ActivityDetailModal extends ModalComponent
{
    use FormatsBeneficiaryChanges;

    public Beneficiary $beneficiary;

    public int $activityId;

    public Activity $activity;

    public function mount(Beneficiary $beneficiary, int $activityId): void
    {
        Gate::authorize('view', $beneficiary);

        $activity = Activity::query()->with('causer')->findOrFail($activityId);

        abort_unless(
            $activity->subject_type === $beneficiary->getMorphClass()
                && (int) $activity->subject_id === $beneficiary->id,
            404,
        );

        $this->beneficiary = $beneficiary;
        $this->activityId = $activityId;
        $this->activity = $activity;
    }

    /**
     * @return array<int, array{field: string, old: ?string, new: ?string}>
     */
    #[Computed]
    public function changes(): array
    {
        return $this->activityChanges($this->activity);
    }

    public static function modalMaxWidth(): string
    {
        return 'lg';
    }

    public function render()
    {
        return view('livewire.beneficiaries.profile.activity-detail-modal');
    }
}

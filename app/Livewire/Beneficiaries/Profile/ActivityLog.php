<?php

namespace App\Livewire\Beneficiaries\Profile;

use App\Livewire\Beneficiaries\Profile\Concerns\FormatsBeneficiaryChanges;
use App\Models\Beneficiary;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Spatie\Activitylog\Models\Activity;

/**
 * "Activity" tab of the beneficiary profile: a compact, read-only
 * timeline. Each row shows only the event title, actor, and relative
 * time — the full field-by-field diff is rendered on demand by
 * ActivityDetailModal (opened via the row's "details" button), keeping
 * the list itself scannable instead of dumping raw attribute_changes.
 */
class ActivityLog extends Component
{
    use FormatsBeneficiaryChanges;

    public Beneficiary $beneficiary;

    public function mount(Beneficiary $beneficiary): void
    {
        $this->beneficiary = $beneficiary;
    }

    /**
     * @return Collection<int, Activity>
     */
    #[Computed]
    public function activities(): Collection
    {
        return Activity::query()
            ->where('subject_type', $this->beneficiary->getMorphClass())
            ->where('subject_id', $this->beneficiary->id)
            ->with('causer')
            ->latest()
            ->limit(50)
            ->get();
    }

    public function render()
    {
        return view('livewire.beneficiaries.profile.activity-log');
    }
}

<?php

namespace App\Livewire\Beneficiaries\Profile;

use App\Models\Beneficiary;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Spatie\Activitylog\Models\Activity;

/**
 * "Activity" tab of the beneficiary profile: read-only history, including
 * bank-data-reveal events logged directly against the beneficiary.
 */
class ActivityLog extends Component
{
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

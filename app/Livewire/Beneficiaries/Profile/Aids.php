<?php

namespace App\Livewire\Beneficiaries\Profile;

use App\Models\Aid;
use App\Models\Beneficiary;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * "Aids" tab of the beneficiary profile: a compact, read-only list of every
 * aid granted to this beneficiary, linking out to each aid's own screen.
 */
class Aids extends Component
{
    public Beneficiary $beneficiary;

    public function mount(Beneficiary $beneficiary): void
    {
        $this->beneficiary = $beneficiary;
    }

    /**
     * This beneficiary's aids, newest first, with their program eager-loaded
     * for the list.
     *
     * @return Collection<int, Aid>
     */
    #[Computed]
    public function aids(): Collection
    {
        return $this->beneficiary->aids()
            ->with('program')
            ->latest()
            ->get();
    }

    public function render()
    {
        return view('livewire.beneficiaries.profile.aids');
    }
}

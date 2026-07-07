<?php

namespace App\Livewire\Beneficiaries;

use App\Models\Beneficiary;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Skeleton for the beneficiary profile screen. Tab contents are
 * implemented in phase 2b; this class only wires up the public property
 * contract, authorization and the target view.
 */
class Show extends Component
{
    public Beneficiary $beneficiary;

    #[Url]
    public string $activeTab = 'basic';

    public function mount(Beneficiary $beneficiary): void
    {
        $this->beneficiary = $beneficiary;

        Gate::authorize('view', $this->beneficiary);
    }

    public function render()
    {
        return view('livewire.beneficiaries.show');
    }
}

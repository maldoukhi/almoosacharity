<?php

namespace App\Livewire\Settings\AidPrograms;

use App\Models\AidProgram;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

/**
 * Skeleton for the aid programs settings screen. Listing and CRUD logic
 * are implemented in phase 3b; this class only wires up the public
 * property contract, authorization and the target view.
 */
class Index extends Component
{
    public string $search = '';

    public function mount(): void
    {
        Gate::authorize('viewAny', AidProgram::class);
    }

    public function render()
    {
        return view('livewire.settings.aid-programs.index');
    }
}

<?php

namespace App\Livewire\Settings\ApprovalFlows;

use App\Models\ApprovalFlow;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

/**
 * Skeleton for the approval flows settings screen. Listing and CRUD
 * logic are implemented in phase 3b; this class only wires up
 * authorization and the target view.
 */
class Index extends Component
{
    public function mount(): void
    {
        Gate::authorize('viewAny', ApprovalFlow::class);
    }

    public function render()
    {
        return view('livewire.settings.approval-flows.index');
    }
}

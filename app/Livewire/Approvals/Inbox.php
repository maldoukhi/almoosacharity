<?php

namespace App\Livewire\Approvals;

use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Skeleton for the approvals inbox: the list of aids awaiting a decision
 * at a stage the current user holds the role for. Query building and
 * decision actions are implemented in phase 3b; this class only wires up
 * the public property contract, authorization and the target view.
 */
class Inbox extends Component
{
    use WithPagination;

    public string $programFilter = '';

    public function mount(): void
    {
        Gate::authorize('approvals.view');
    }

    public function render()
    {
        return view('livewire.approvals.inbox');
    }
}

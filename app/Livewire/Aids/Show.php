<?php

namespace App\Livewire\Aids;

use App\Models\Aid;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

/**
 * Skeleton for the aid detail screen (timeline, decisions, items). Tab
 * contents and the approval decision form are implemented in phase 3b;
 * this class only wires up the public property contract, authorization
 * and the target view.
 */
class Show extends Component
{
    public Aid $aid;

    public string $decisionNote = '';

    public function mount(Aid $aid): void
    {
        $this->aid = $aid;

        Gate::authorize('view', $this->aid);
    }

    public function render()
    {
        return view('livewire.aids.show');
    }
}

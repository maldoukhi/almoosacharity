<?php

namespace App\Livewire\Reports;

use Illuminate\Support\Facades\Gate;
use Livewire\Component;

/**
 * Reports landing screen: cards linking to each of the four reports.
 */
class Index extends Component
{
    public function mount(): void
    {
        Gate::authorize('reports.view');
    }

    public function render()
    {
        return view('livewire.reports.index');
    }
}

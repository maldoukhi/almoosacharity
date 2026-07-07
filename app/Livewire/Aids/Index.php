<?php

namespace App\Livewire\Aids;

use App\Models\Aid;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Skeleton for the aids listing screen. Query building, filters and row
 * actions are implemented in phase 3b; this class only wires up the
 * public property contract, authorization and the target view.
 */
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $statusFilter = '';

    #[Url]
    public string $programFilter = '';

    #[Url]
    public string $typeFilter = '';

    #[Url]
    public string $beneficiaryFilter = '';

    public function mount(): void
    {
        Gate::authorize('viewAny', Aid::class);
    }

    public function render()
    {
        return view('livewire.aids.index');
    }
}

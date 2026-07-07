<?php

namespace App\Livewire\Beneficiaries;

use App\Models\Beneficiary;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Skeleton for the beneficiaries listing screen. Query building, filters
 * and row actions are implemented in phase 2b; this class only wires up
 * the public property contract, authorization and the target view.
 */
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $categoryFilter = '';

    #[Url]
    public string $statusFilter = '';

    #[Url]
    public string $cityFilter = '';

    public bool $trashed = false;

    public function mount(): void
    {
        Gate::authorize('viewAny', Beneficiary::class);
    }

    public function render()
    {
        return view('livewire.beneficiaries.index');
    }
}

<?php

namespace App\Livewire\Settings\Categories;

use App\Models\BeneficiaryCategory;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

/**
 * Skeleton for the beneficiary categories settings screen. Listing and
 * CRUD logic are implemented in phase 2b; this class only wires up the
 * public property contract, authorization and the target view.
 */
class Index extends Component
{
    public ?int $editingId = null;

    public string $name = '';

    public string $description = '';

    public bool $is_active = true;

    public function mount(): void
    {
        Gate::authorize('viewAny', BeneficiaryCategory::class);
    }

    public function render()
    {
        return view('livewire.settings.categories.index');
    }
}

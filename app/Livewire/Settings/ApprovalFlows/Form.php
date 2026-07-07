<?php

namespace App\Livewire\Settings\ApprovalFlows;

use App\Models\ApprovalFlow;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

/**
 * Skeleton for the approval flow create/edit form (flow + its ordered
 * stages). Field population, validation and saving (via
 * SaveApprovalFlow) are implemented in phase 3b; this class only wires
 * up the public property contract, authorization and the target view.
 */
class Form extends Component
{
    public ?ApprovalFlow $flow = null;

    public string $name = '';

    public bool $is_default = false;

    public bool $is_active = true;

    /** @var array<int, array{name: string, role: string, allowed_actions: array<int, string>}> */
    public array $stages = [];

    public function mount(?ApprovalFlow $flow = null): void
    {
        $this->flow = $flow;

        Gate::authorize('manage', $this->flow ?? ApprovalFlow::class);
    }

    public function render()
    {
        return view('livewire.settings.approval-flows.form');
    }
}

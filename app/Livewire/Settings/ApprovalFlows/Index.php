<?php

namespace App\Livewire\Settings\ApprovalFlows;

use App\Models\ApprovalFlow;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Approval flows settings screen: listing, set-default and delete.
 */
class Index extends Component
{
    public function mount(): void
    {
        Gate::authorize('viewAny', ApprovalFlow::class);
    }

    /**
     * @return Collection<int, ApprovalFlow>
     */
    #[Computed]
    public function flows(): Collection
    {
        return ApprovalFlow::query()
            ->with('stages')
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();
    }

    /**
     * Make the given flow the default one, clearing the flag on every
     * other flow atomically. Refuses to default to an inactive flow.
     */
    public function setDefault(int $id): void
    {
        $flow = ApprovalFlow::findOrFail($id);

        Gate::authorize('manage', $flow);

        if (! $flow->is_active) {
            $this->dispatch('toast', type: 'error', message: __('approvals.flows.messages.must_be_active'));

            return;
        }

        DB::transaction(function () use ($flow): void {
            ApprovalFlow::query()->whereKeyNot($flow->id)->update(['is_default' => false]);
            $flow->update(['is_default' => true]);
        });

        unset($this->flows);

        $this->dispatch('toast', type: 'success', message: __('approvals.flows.messages.default_set'));
    }

    /**
     * Refuses to delete the default flow, or one still referenced by a
     * program or an aid (its snapshot would be left dangling).
     */
    public function delete(int $id): void
    {
        $flow = ApprovalFlow::findOrFail($id);

        Gate::authorize('manage', $flow);

        if ($flow->is_default) {
            $this->dispatch('toast', type: 'error', message: __('approvals.flows.messages.cannot_delete_default'));

            return;
        }

        if ($flow->aids()->exists() || $flow->programs()->exists()) {
            $this->dispatch('toast', type: 'error', message: __('approvals.flows.messages.cannot_delete_in_use'));

            return;
        }

        $flow->delete();

        unset($this->flows);

        $this->dispatch('toast', type: 'success', message: __('approvals.flows.messages.deleted'));
    }

    public function render()
    {
        return view('livewire.settings.approval-flows.index');
    }
}

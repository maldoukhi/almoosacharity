<?php

namespace App\Livewire\Settings\BeneficiaryFlows;

use App\Models\BeneficiaryFlow;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Beneficiary review flows settings screen: listing, set-default and delete.
 */
class Index extends Component
{
    public function mount(): void
    {
        Gate::authorize('viewAny', BeneficiaryFlow::class);
    }

    /**
     * @return Collection<int, BeneficiaryFlow>
     */
    #[Computed]
    public function flows(): Collection
    {
        return BeneficiaryFlow::query()
            ->with('stages')
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();
    }

    /**
     * Make the given flow the default one, clearing the flag on every other
     * flow atomically. Refuses to default to an inactive flow.
     */
    public function setDefault(int $id): void
    {
        $flow = BeneficiaryFlow::findOrFail($id);

        Gate::authorize('manage', $flow);

        if (! $flow->is_active) {
            $this->dispatch('toast', type: 'error', message: __('beneficiaries.flow.messages.must_be_active'));

            return;
        }

        DB::transaction(function () use ($flow): void {
            BeneficiaryFlow::query()->whereKeyNot($flow->id)->update(['is_default' => false]);
            $flow->update(['is_default' => true]);
        });

        unset($this->flows);

        $this->dispatch('toast', type: 'success', message: __('beneficiaries.flow.messages.default_set'));
    }

    /**
     * Refuses to delete the default flow, or one still referenced by a
     * beneficiary (its snapshot would be left dangling).
     */
    public function delete(int $id): void
    {
        $flow = BeneficiaryFlow::findOrFail($id);

        Gate::authorize('manage', $flow);

        if ($flow->is_default) {
            $this->dispatch('toast', type: 'error', message: __('beneficiaries.flow.messages.cannot_delete_default'));

            return;
        }

        if ($flow->beneficiaries()->exists()) {
            $this->dispatch('toast', type: 'error', message: __('beneficiaries.flow.messages.cannot_delete_in_use'));

            return;
        }

        $flow->delete();

        unset($this->flows);

        $this->dispatch('toast', type: 'success', message: __('beneficiaries.flow.messages.deleted'));
    }

    public function render()
    {
        return view('livewire.settings.beneficiary-flows.index');
    }
}

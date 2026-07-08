<?php

namespace App\Livewire\Settings\AidPrograms;

use App\Actions\Settings\SaveAidProgram;
use App\Models\AidProgram;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Aid programs settings screen: listing, activation toggle and delete.
 */
class Index extends Component
{
    public string $search = '';

    public function mount(): void
    {
        Gate::authorize('viewAny', AidProgram::class);
    }

    /**
     * Refresh the list after the create/edit modal saves a program.
     */
    #[On('aid-program-saved')]
    public function refreshList(): void
    {
        unset($this->programs);
    }

    /**
     * @return Collection<int, AidProgram>
     */
    #[Computed]
    public function programs(): Collection
    {
        return AidProgram::query()
            ->with('approvalFlow')
            ->withCount('aids')
            ->when($this->search !== '', function ($query): void {
                $query->where('name', 'like', "%{$this->search}%");
            })
            ->orderBy('sort_order')
            ->get();
    }

    public function toggleActive(int $id): void
    {
        $program = AidProgram::findOrFail($id);

        Gate::authorize('manage', $program);

        app(SaveAidProgram::class)->handle([
            'name' => $program->name,
            'type' => $program->type->value,
            'approval_flow_id' => $program->approval_flow_id,
            'is_active' => ! $program->is_active,
            'sort_order' => $program->sort_order,
            'description' => $program->description,
        ], $program);

        unset($this->programs);
    }

    /**
     * Soft-delete a program, refusing to do so while it still has aids
     * attached to it (they would be left pointing at a deleted program).
     */
    public function delete(int $id): void
    {
        $program = AidProgram::findOrFail($id);

        Gate::authorize('manage', $program);

        if ($program->aids()->exists()) {
            $this->dispatch('toast', type: 'error', message: __('aids.programs.messages.cannot_delete_in_use'));

            return;
        }

        $program->delete();

        unset($this->programs);

        $this->dispatch('toast', type: 'success', message: __('aids.programs.messages.deleted'));
    }

    public function render()
    {
        return view('livewire.settings.aid-programs.index');
    }
}
